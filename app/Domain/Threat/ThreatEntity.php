<?php
/**
 * AegisZ Sentinel - Threat Entity (v0.6.0)
 * Extended with cve_id, affected_systems.
 */

namespace App\Domain\Threat;

class ThreatEntity
{
    public ?int    $id              = null;
    public string  $title           = '';
    public ?string $description     = null;
    public ?string $sourceFeed      = null;
    public string  $severity        = 'medium';
    public ?string $mitreTechnique  = null;
    public ?array  $rawData         = null;
    // v0.6.0
    public ?string $cveId           = null;
    public ?string $affectedSystems = null;
    public ?string $createdAt       = null;
    public ?string $updatedAt       = null;

    public static function fromArray(array $data): self
    {
        $entity                  = new self();
        $entity->id              = isset($data['id']) ? (int) $data['id'] : null;
        $entity->title           = $data['title'] ?? '';
        $entity->description     = $data['description'] ?? null;
        $entity->sourceFeed      = $data['source_feed'] ?? null;
        $entity->severity        = $data['severity'] ?? 'medium';
        $entity->mitreTechnique  = $data['mitre_technique'] ?? null;
        $entity->rawData         = isset($data['raw_data']) ? json_decode($data['raw_data'], true) : null;
        $entity->cveId           = $data['cve_id'] ?? null;
        $entity->affectedSystems = $data['affected_systems'] ?? null;
        $entity->createdAt       = $data['created_at'] ?? null;
        $entity->updatedAt       = $data['updated_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'source_feed'      => $this->sourceFeed,
            'severity'         => $this->severity,
            'mitre_technique'  => $this->mitreTechnique,
            'raw_data'         => $this->rawData ? json_encode($this->rawData) : null,
            'cve_id'           => $this->cveId,
            'affected_systems' => $this->affectedSystems,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty(trim($this->title))) {
            $errors[] = 'Threat title is required.';
        }
        if (!in_array($this->severity, ['low', 'medium', 'high', 'critical'])) {
            $errors[] = 'Invalid severity level.';
        }
        return $errors;
    }
}
