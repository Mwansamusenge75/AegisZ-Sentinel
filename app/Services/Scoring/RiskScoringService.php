<?php
/**
 * AegisZ Sentinel - Risk Scoring Service (v0.4.0)
 * Calculates a transparent Security Posture Score (0–100).
 * Every deduction is explicitly explained. No black-box scoring.
 *
 * Architecture: Service → Business Logic only.
 */

namespace App\Services\Scoring;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class RiskScoringService
{
    private PDO $db;
    private Logger $logger;

    // Maximum deduction per category (sum should not exceed 100)
    private const MAX_DEDUCTIONS = [
        'critical_threats'     => 20,
        'critical_cves'        => 15,
        'open_incidents'       => 20,
        'high_confidence_iocs' => 15,
        'critical_assets_hit'  => 20,
        'open_critical_alerts' => 10,
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Calculate the current Security Posture Score.
     * Returns ['score' => int, 'breakdown' => array, 'rating' => string]
     */
    public function calculate(): array
    {
        $this->logger->info('[RiskScoring] Calculating Security Posture Score');

        $breakdown = [];
        $totalDeduction = 0;

        // --- Factor 1: Critical threats ingested in last 7 days ---
        $criticalThreats = $this->countCriticalThreats();
        $deduction = $this->scaleDeduction($criticalThreats, 5, 20, self::MAX_DEDUCTIONS['critical_threats']);
        if ($deduction > 0) {
            $breakdown[] = [
                'factor'      => 'Critical Threats (last 7 days)',
                'value'       => $criticalThreats,
                'deduction'   => -$deduction,
                'explanation' => "{$criticalThreats} critical-severity threat(s) ingested in the last 7 days. Each cluster of 5 adds -{$deduction} points (max -" . self::MAX_DEDUCTIONS['critical_threats'] . ").",
            ];
            $totalDeduction += $deduction;
        }

        // --- Factor 2: Critical CVEs (NVD/CISA) in last 30 days ---
        $criticalCves = $this->countCriticalCVEs();
        $deduction = $this->scaleDeduction($criticalCves, 3, 15, self::MAX_DEDUCTIONS['critical_cves']);
        if ($deduction > 0) {
            $breakdown[] = [
                'factor'      => 'Critical CVEs (last 30 days)',
                'value'       => $criticalCves,
                'deduction'   => -$deduction,
                'explanation' => "{$criticalCves} critical CVE(s) from NVD/CISA in the last 30 days. Each cluster of 3 adds -{$deduction} points (max -" . self::MAX_DEDUCTIONS['critical_cves'] . ").",
            ];
            $totalDeduction += $deduction;
        }

        // --- Factor 3: Open incidents by severity ---
        $openIncidents = $this->countOpenIncidents();
        $incidentDeduction = min(
            self::MAX_DEDUCTIONS['open_incidents'],
            ($openIncidents['critical'] * 8) + ($openIncidents['high'] * 4) + ($openIncidents['medium'] * 2)
        );
        if ($incidentDeduction > 0) {
            $breakdown[] = [
                'factor'    => 'Open Incidents',
                'value'     => $openIncidents['total'],
                'deduction' => -$incidentDeduction,
                'explanation' => sprintf(
                    "%d open incident(s): %d critical (-8 each), %d high (-4 each), %d medium (-2 each). Total deduction capped at -%d.",
                    $openIncidents['total'],
                    $openIncidents['critical'],
                    $openIncidents['high'],
                    $openIncidents['medium'],
                    self::MAX_DEDUCTIONS['open_incidents']
                ),
            ];
            $totalDeduction += $incidentDeduction;
        }

        // --- Factor 4: High-confidence IOC volume ---
        $highConfidenceIocs = $this->countHighConfidenceIOCs();
        $deduction = $this->scaleDeduction($highConfidenceIocs, 10, 15, self::MAX_DEDUCTIONS['high_confidence_iocs']);
        if ($deduction > 0) {
            $breakdown[] = [
                'factor'      => 'High-Confidence IOCs (≥80%)',
                'value'       => $highConfidenceIocs,
                'deduction'   => -$deduction,
                'explanation' => "{$highConfidenceIocs} IOC(s) with confidence ≥80%. High-confidence indicators signal active threats. Each cluster of 10 adds -{$deduction} points (max -" . self::MAX_DEDUCTIONS['high_confidence_iocs'] . ").",
            ];
            $totalDeduction += $deduction;
        }

        // --- Factor 5: Critical assets involved in correlations ---
        $criticalAssetsHit = $this->countCriticalAssetsInCorrelations();
        $deduction = min(self::MAX_DEDUCTIONS['critical_assets_hit'], $criticalAssetsHit * 5);
        if ($deduction > 0) {
            $breakdown[] = [
                'factor'      => 'Critical Assets in Active Correlations',
                'value'       => $criticalAssetsHit,
                'deduction'   => -$deduction,
                'explanation' => "{$criticalAssetsHit} critical asset(s) appear in active threat correlations. -5 points per critical asset (max -" . self::MAX_DEDUCTIONS['critical_assets_hit'] . ").",
            ];
            $totalDeduction += $deduction;
        }

        // --- Factor 6: Open critical alerts ---
        $openCriticalAlerts = $this->countOpenCriticalAlerts();
        $deduction = min(self::MAX_DEDUCTIONS['open_critical_alerts'], $openCriticalAlerts * 2);
        if ($deduction > 0) {
            $breakdown[] = [
                'factor'      => 'Open Critical Alerts',
                'value'       => $openCriticalAlerts,
                'deduction'   => -$deduction,
                'explanation' => "{$openCriticalAlerts} open critical-severity alert(s). -2 points each (max -" . self::MAX_DEDUCTIONS['open_critical_alerts'] . ").",
            ];
            $totalDeduction += $deduction;
        }

        $score = max(0, min(100, 100 - $totalDeduction));
        $rating = $this->deriveRating($score);

        $this->logger->info('[RiskScoring] Score calculated', [
            'score'           => $score,
            'total_deduction' => $totalDeduction,
            'rating'          => $rating,
        ]);

        return [
            'score'     => $score,
            'rating'    => $rating,
            'deduction' => $totalDeduction,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Persist score snapshot to history table.
     */
    public function persist(array $result): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO risk_score_history (score, rating, total_deduction, breakdown_json)
             VALUES (:score, :rating, :deduction, :breakdown)"
        );
        $stmt->execute([
            'score'     => $result['score'],
            'rating'    => $result['rating'],
            'deduction' => $result['deduction'],
            'breakdown' => json_encode($result['breakdown']),
        ]);
        $this->logger->info('[RiskScoring] Score persisted to history');
    }

    /**
     * Retrieve the most recent stored score.
     */
    public function getLatest(): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM risk_score_history ORDER BY created_at DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['breakdown'] = json_decode($row['breakdown_json'], true) ?? [];
        return $row;
    }

    /**
     * Retrieve score history for trend display (last N records).
     */
    public function getHistory(int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT score, rating, created_at FROM risk_score_history
             ORDER BY created_at DESC LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // =========================================================
    // Private helpers
    // =========================================================

    private function countCriticalThreats(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM threats
             WHERE severity = 'critical'
               AND created_at >= NOW() - INTERVAL 7 DAY"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countCriticalCVEs(): int
    {
        // NVD and CISA both store as threats; we detect by source_feed
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM threats
             WHERE severity = 'critical'
               AND source_feed IN ('nvd', 'cisa')
               AND created_at >= NOW() - INTERVAL 30 DAY"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countOpenIncidents(): array
    {
        $stmt = $this->db->prepare(
            "SELECT severity, COUNT(*) AS cnt FROM incidents
             WHERE status IN ('open', 'investigating')
             GROUP BY severity"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'critical' => (int) ($rows['critical'] ?? 0),
            'high'     => (int) ($rows['high'] ?? 0),
            'medium'   => (int) ($rows['medium'] ?? 0),
            'low'      => (int) ($rows['low'] ?? 0),
            'total'    => array_sum($rows),
        ];
    }

    private function countHighConfidenceIOCs(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM iocs WHERE confidence_score >= 80"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countCriticalAssetsInCorrelations(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(DISTINCT c.asset_id) FROM correlations c
             INNER JOIN assets a ON c.asset_id = a.id
             WHERE a.criticality = 'critical'
               AND c.created_at >= NOW() - INTERVAL 7 DAY"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countOpenCriticalAlerts(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM alerts
             WHERE status = 'open' AND severity = 'critical'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Scale a count linearly to a deduction.
     * $clusterSize: how many items = 1 point increment
     * $pointsPerCluster: how many points per cluster
     * $max: cap
     */
    private function scaleDeduction(int $count, int $clusterSize, int $pointsPerCluster, int $max): int
    {
        if ($count === 0) {
            return 0;
        }
        $clusters = (int) ceil($count / $clusterSize);
        return min($max, $clusters * (int) ($pointsPerCluster / max(1, (int) ceil(20 / $clusterSize))));
    }

    /**
     * Derive a human-readable security rating from score.
     */
    private function deriveRating(int $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'At Risk';
        return 'Critical';
    }
}
