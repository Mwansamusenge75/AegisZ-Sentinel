<?php
/**
 * AegisZ Sentinel - Incident Entity
 * Data structure representing a security incident.
 */

namespace App\Domain\Incident;

class IncidentEntity
{
    public ?int $id = null;
    public string $title = '';
    public string $status = 'open';
    public string $severity = 'medium';
    public ?int $linkedAlertId = null;
    public ?int $linkedAssetId = null;
    public ?array $timeline = null;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public static function fromArray(array $data): self
    {
        $entity = new self();
        $entity->id = isset($data['id']) ? (int) $data['id'] : null;
        $entity->title = $data['title'] ?? '';
        $entity->status = $data['status'] ?? 'open';
        $entity->severity = $data['severity'] ?? 'medium';
        $entity->linkedAlertId = isset($data['linked_alert_id']) ? (int) $data['linked_alert_id'] : null;
        $entity->linkedAssetId = isset($data['linked_asset_id']) ? (int) $data['linked_asset_id'] : null;
        $entity->timeline = isset($data['timeline']) ? json_decode($data['timeline'], true) : null;
        $entity->createdAt = $data['created_at'] ?? null;
        $entity->updatedAt = $data['updated_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'severity' => $this->severity,
            'linked_alert_id' => $this->linkedAlertId,
            'linked_asset_id' => $this->linkedAssetId,
            'timeline' => $this->timeline ? json_encode($this->timeline) : null,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->title)) {
            $errors[] = 'Incident title is required.';
        }
        if (!in_array($this->status, ['open', 'investigating', 'contained', 'resolved', 'closed'])) {
            $errors[] = 'Invalid incident status.';
        }
        if (!in_array($this->severity, ['low', 'medium', 'high', 'critical'])) {
            $errors[] = 'Invalid severity level.';
        }
        return $errors;
    }
}
