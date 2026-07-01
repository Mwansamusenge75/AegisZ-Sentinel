<?php
/**
 * AegisZ Sentinel - MITRE ATT&CK Mapping Service (v0.4.0)
 * Maps collected threat data to MITRE ATT&CK techniques using rule-based matching.
 * No external API calls. No black-box inference.
 *
 * Architecture: Service → Business Logic only.
 */

namespace App\Services\ThreatIntel;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class MitreMappingService
{
    private PDO $db;
    private Logger $logger;

    /**
     * Curated MITRE ATT&CK technique registry.
     * Format: technique_id => ['name', 'tactic', 'keywords']
     */
    private const TECHNIQUES = [
        'T1566' => [
            'name'     => 'Phishing',
            'tactic'   => 'Initial Access',
            'keywords' => ['phish', 'phishing', 'spear', 'lure', 'bait', 'credential harvest', 'email attachment', 'malicious link'],
        ],
        'T1059' => [
            'name'     => 'Command and Scripting Interpreter',
            'tactic'   => 'Execution',
            'keywords' => ['powershell', 'cmd', 'bash', 'shell', 'script', 'interpreter', 'wscript', 'cscript', 'python'],
        ],
        'T1071' => [
            'name'     => 'Application Layer Protocol',
            'tactic'   => 'Command and Control',
            'keywords' => ['http', 'https', 'dns', 'ftp', 'smtp', 'c2', 'command and control', 'beaconing', 'beacon'],
        ],
        'T1041' => [
            'name'     => 'Exfiltration Over C2 Channel',
            'tactic'   => 'Exfiltration',
            'keywords' => ['exfil', 'exfiltration', 'data theft', 'data leak', 'upload', 'transmit', 'steal'],
        ],
        'T1105' => [
            'name'     => 'Ingress Tool Transfer',
            'tactic'   => 'Command and Control',
            'keywords' => ['dropper', 'downloader', 'download', 'tool transfer', 'malware download', 'payload'],
        ],
        'T1190' => [
            'name'     => 'Exploit Public-Facing Application',
            'tactic'   => 'Initial Access',
            'keywords' => ['exploit', 'exploitation', 'vulnerability', 'cve', 'rce', 'remote code execution', 'web application'],
        ],
        'T1486' => [
            'name'     => 'Data Encrypted for Impact',
            'tactic'   => 'Impact',
            'keywords' => ['ransomware', 'encrypt', 'ransom', 'lockbit', 'conti', 'blackcat', 'data encrypted'],
        ],
        'T1110' => [
            'name'     => 'Brute Force',
            'tactic'   => 'Credential Access',
            'keywords' => ['brute force', 'bruteforce', 'password spray', 'credential stuffing', 'dictionary attack'],
        ],
        'T1078' => [
            'name'     => 'Valid Accounts',
            'tactic'   => 'Defense Evasion',
            'keywords' => ['valid account', 'stolen credential', 'compromised account', 'account takeover', 'credential abuse'],
        ],
        'T1133' => [
            'name'     => 'External Remote Services',
            'tactic'   => 'Initial Access',
            'keywords' => ['vpn', 'rdp', 'remote desktop', 'ssh', 'remote access', 'teamviewer', 'anydesk'],
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Map all unmapped threats to MITRE techniques using keyword rules.
     * Returns ['mapped' => int, 'skipped' => int]
     */
    public function mapThreats(): array
    {
        $this->logger->info('[MitreMapping] Starting threat mapping run');

        $mapped  = 0;
        $skipped = 0;

        // Fetch threats with no MITRE technique assigned
        $stmt = $this->db->prepare(
            "SELECT id, title, description, source_feed FROM threats
             WHERE (mitre_technique IS NULL OR mitre_technique = '')
             ORDER BY created_at DESC
             LIMIT 500"
        );
        $stmt->execute();
        $threats = $stmt->fetchAll();

        foreach ($threats as $threat) {
            $techniqueId = $this->matchTechnique($threat['title'], $threat['description'] ?? '');
            if ($techniqueId === null) {
                $skipped++;
                continue;
            }

            // Update the threat record
            $updateStmt = $this->db->prepare(
                "UPDATE threats SET mitre_technique = :technique WHERE id = :id"
            );
            $updateStmt->execute([
                'technique' => $techniqueId,
                'id'        => $threat['id'],
            ]);

            // Record in mitre_mappings table
            $this->recordMapping($threat['id'], $techniqueId, $threat['source_feed'] ?? 'unknown');

            $mapped++;
        }

        $this->logger->info('[MitreMapping] Mapping run completed', [
            'mapped'  => $mapped,
            'skipped' => $skipped,
        ]);

        return ['mapped' => $mapped, 'skipped' => $skipped];
    }

    /**
     * Match a text body to a MITRE technique ID using keyword rules.
     * Returns the best-matching technique ID, or null if no match.
     */
    public function matchTechnique(string $title, string $description = ''): ?string
    {
        $haystack = strtolower($title . ' ' . $description);
        $best     = null;
        $bestScore = 0;

        foreach (self::TECHNIQUES as $id => $technique) {
            $score = 0;
            foreach ($technique['keywords'] as $keyword) {
                if (strpos($haystack, $keyword) !== false) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $id;
            }
        }

        return $bestScore > 0 ? $best : null;
    }

    /**
     * Get distribution of MITRE techniques across all threats.
     */
    public function getTechniqueDistribution(): array
    {
        $stmt = $this->db->query(
            "SELECT mitre_technique, COUNT(*) AS count
             FROM threats
             WHERE mitre_technique IS NOT NULL AND mitre_technique != ''
             GROUP BY mitre_technique
             ORDER BY count DESC"
        );
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Enrich with technique names
        $enriched = [];
        foreach ($rows as $techniqueId => $count) {
            $info = self::TECHNIQUES[$techniqueId] ?? null;
            $enriched[] = [
                'technique_id'   => $techniqueId,
                'technique_name' => $info ? $info['name'] : $techniqueId,
                'tactic'         => $info ? $info['tactic'] : 'Unknown',
                'count'          => (int) $count,
            ];
        }

        return $enriched;
    }

    /**
     * Get the full technique registry (for reference display).
     */
    public function getTechniqueRegistry(): array
    {
        $result = [];
        foreach (self::TECHNIQUES as $id => $info) {
            $result[] = array_merge(['id' => $id], $info);
        }
        return $result;
    }

    /**
     * Resolve a technique ID to its display name and tactic.
     */
    public function resolveId(string $techniqueId): array
    {
        if (isset(self::TECHNIQUES[$techniqueId])) {
            return array_merge(['id' => $techniqueId], self::TECHNIQUES[$techniqueId]);
        }
        return ['id' => $techniqueId, 'name' => $techniqueId, 'tactic' => 'Unknown', 'keywords' => []];
    }

    /**
     * Record a mapping event in the mitre_mappings audit table.
     */
    private function recordMapping(int $threatId, string $techniqueId, string $sourceFeed): void
    {
        $info = self::TECHNIQUES[$techniqueId];
        $stmt = $this->db->prepare(
            "INSERT INTO mitre_mappings
             (threat_id, technique_id, technique_name, tactic, source_feed, match_method)
             VALUES (:threat_id, :technique_id, :technique_name, :tactic, :source_feed, 'keyword')"
        );
        $stmt->execute([
            'threat_id'      => $threatId,
            'technique_id'   => $techniqueId,
            'technique_name' => $info['name'],
            'tactic'         => $info['tactic'],
            'source_feed'    => $sourceFeed,
        ]);
    }
}
