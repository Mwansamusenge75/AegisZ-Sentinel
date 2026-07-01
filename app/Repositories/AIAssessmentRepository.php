<?php
/**
 * AegisZ Sentinel - AI Assessment Repository (v0.7.0)
 * All SQL for ai_assessments and ai_explanations cache tables.
 * Pure read/write of CACHE data — never touches operational tables
 * (alerts, incidents, correlations, threats, risk_score_history).
 */

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AIAssessmentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getLatestAssessment(): ?array
    {
        $stmt = $this->db->query(
            "SELECT * FROM ai_assessments ORDER BY created_at DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['payload'] = json_decode($row['payload_json'], true);
        return $row;
    }

    public function saveAssessment(array $payload, string $model): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ai_assessments (payload_json, model, threat_level, confidence)
             VALUES (:payload, :model, :threat_level, :confidence)"
        );
        $stmt->execute([
            'payload'      => json_encode($payload),
            'model'        => $model,
            'threat_level' => $payload['threat_level'] ?? null,
            'confidence'   => $payload['confidence'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function isStale(int $maxAgeMinutes): bool
    {
        $latest = $this->getLatestAssessment();
        if (!$latest) return true;
        $ageSeconds = time() - strtotime($latest['created_at']);
        return $ageSeconds > ($maxAgeMinutes * 60);
    }

    // ---- Per-object explanation cache ----

    public function getCachedExplanation(string $objectType, int $objectId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM ai_explanations
             WHERE object_type = :type AND object_id = :id
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute(['type' => $objectType, 'id' => $objectId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['payload'] = json_decode($row['payload_json'], true);
        return $row;
    }

    public function saveExplanation(string $objectType, int $objectId, array $payload, string $model): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ai_explanations (object_type, object_id, payload_json, model)
             VALUES (:type, :id, :payload, :model)"
        );
        $stmt->execute([
            'type'    => $objectType,
            'id'      => $objectId,
            'payload' => json_encode($payload),
            'model'   => $model,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
