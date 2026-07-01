<?php
/**
 * AegisZ Sentinel - Alert Entity (v0.5.0)
 * Extended with assigned_to and full lifecycle statuses.
 */

namespace App\Domain\Alert;

class AlertEntity
{
    public ?int    $id             = null;
    public string  $title          = '';
    public string  $severity       = 'medium';
    public string  $status         = 'open';
    public ?int    $linkedIocId    = null;
    public ?int    $linkedAssetId  = null;
    public ?int    $assignedTo     = null;
    public ?string $createdAt      = null;
    public ?string $updatedAt      = null;

    public static function fromArray(array $data): self
    {
        $entity                = new self();
        $entity->id            = isset($data['id']) ? (int) $data['id'] : null;
        $entity->title         = $data['title'] ?? '';
        $entity->severity      = $data['severity'] ?? 'medium';
        $entity->status        = $data['status'] ?? 'open';
        $entity->linkedIocId   = isset($data['linked_ioc_id']) ? (int) $data['linked_ioc_id'] : null;
        $entity->linkedAssetId = isset($data['linked_asset_id']) ? (int) $data['linked_asset_id'] : null;
        $entity->assignedTo    = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        $entity->createdAt     = $data['created_at'] ?? null;
        $entity->updatedAt     = $data['updated_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'severity'         => $this->severity,
            'status'           => $this->status,
            'linked_ioc_id'    => $this->linkedIocId,
            'linked_asset_id'  => $this->linkedAssetId,
            'assigned_to'      => $this->assignedTo,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->title)) {
            $errors[] = 'Alert title is required.';
        }
        if (!in_array($this->severity, ['low', 'medium', 'high', 'critical'])) {
            $errors[] = 'Invalid severity level.';
        }
        if (!in_array($this->status, ['open', 'acknowledged', 'assigned', 'escalated', 'resolved', 'closed'])) {
            $errors[] = 'Invalid status.';
        }
        return $errors;
    }
}
