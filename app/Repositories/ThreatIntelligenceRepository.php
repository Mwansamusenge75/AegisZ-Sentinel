<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Entities\ThreatIntelligence;
use PDO;

class ThreatIntelligenceRepository
{
    protected PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(ThreatIntelligence $threat): void
    {
        $payload = json_encode([
            'affected_assets' => $threat->getAffectedAssets(),
            'related_iocs' => $threat->getRelatedIocs(),
            'related_cves' => $threat->getRelatedCves(),
            'mitre_techniques' => $threat->getMitreTechniques(),
            'recommended_action' => $threat->getRecommendedAction(),
        ]);

        if ($threat->getId()) {
            $stmt = $this->db->prepare(
                "UPDATE threat_intelligence 
                 SET title=?, severity=?, confidence_score=?, data=? 
                 WHERE id=?"
            );
            $stmt->execute([
                $threat->getTitle(), $threat->getSeverity(),
                $threat->getConfidenceScore(), $payload, $threat->getId()
            ]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO threat_intelligence 
                 (title, severity, confidence_score, data, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $threat->getTitle(), $threat->getSeverity(),
                $threat->getConfidenceScore(), $payload
            ]);
            $threat->setId((int)$this->db->lastInsertId());
        }
    }

    public function findRecent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM threat_intelligence ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findBySeverity(string $severity): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM threat_intelligence WHERE severity = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$severity]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countBySeverity(): array
    {
        $stmt = $this->db->query(
            "SELECT severity, COUNT(*) as count FROM threat_intelligence GROUP BY severity"
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function findActiveInLastDays(int $days = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM threat_intelligence 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
             ORDER BY confidence_score DESC"
        );
        $stmt->execute([$days]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): ThreatIntelligence
    {
        $data = json_decode($row['data'] ?? '{}', true);
        $t = new ThreatIntelligence();
        $t->setId((int)$row['id']);
        $t->setTitle($row['title']);
        $t->setSeverity($row['severity']);
        $t->setConfidenceScore((int)$row['confidence_score']);
        $t->setAffectedAssets($data['affected_assets'] ?? []);
        $t->setRelatedIocs($data['related_iocs'] ?? []);
        $t->setRelatedCves($data['related_cves'] ?? []);
        $t->setMitreTechniques($data['mitre_techniques'] ?? []);
        $t->setRecommendedAction($data['recommended_action'] ?? '');
        $t->setCreatedAt($row['created_at']);
        return $t;
    }
}
