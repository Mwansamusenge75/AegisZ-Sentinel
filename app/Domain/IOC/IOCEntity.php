<?php
/**
 * AegisZ Sentinel - IOC Entity (v0.6.0)
 * Extended with: false_positive, expiry_at, tags.
 */

namespace App\Domain\IOC;

class IOCEntity
{
    public ?int    $id              = null;
    public string  $type            = 'ip';
    public string  $value           = '';
    public ?string $source          = null;
    public ?int    $confidenceScore = null;
    public ?string $firstSeen       = null;
    public ?string $lastSeen        = null;
    public ?array  $rawData         = null;
    // v0.6.0 additions
    public bool    $falsePositive   = false;
    public ?string $expiryAt        = null;
    public ?array  $tags            = null;
    public ?string $createdAt       = null;
    public ?string $updatedAt       = null;

    public static function fromArray(array $data): self
    {
        $entity                 = new self();
        $entity->id             = isset($data['id']) ? (int) $data['id'] : null;
        $entity->type           = $data['type'] ?? 'ip';
        $entity->value          = $data['value'] ?? '';
        $entity->source         = $data['source'] ?? null;
        $entity->confidenceScore = isset($data['confidence_score']) ? (int) $data['confidence_score'] : null;
        $entity->firstSeen      = $data['first_seen'] ?? null;
        $entity->lastSeen       = $data['last_seen'] ?? null;
        $entity->rawData        = isset($data['raw_data']) ? json_decode($data['raw_data'], true) : null;
        $entity->falsePositive  = (bool) ($data['false_positive'] ?? false);
        $entity->expiryAt       = $data['expiry_at'] ?? null;
        $entity->tags           = isset($data['tags']) ? json_decode($data['tags'], true) : null;
        $entity->createdAt      = $data['created_at'] ?? null;
        $entity->updatedAt      = $data['updated_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'type'             => $this->type,
            'value'            => $this->value,
            'source'           => $this->source,
            'confidence_score' => $this->confidenceScore,
            'first_seen'       => $this->firstSeen,
            'last_seen'        => $this->lastSeen,
            'raw_data'         => $this->rawData ? json_encode($this->rawData) : null,
            'false_positive'   => $this->falsePositive ? 1 : 0,
            'expiry_at'        => $this->expiryAt,
            'tags'             => $this->tags ? json_encode($this->tags) : null,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty(trim($this->value))) {
            $errors[] = 'IOC value is required.';
        }
        if (!in_array($this->type, ['ip', 'domain', 'url', 'hash'])) {
            $errors[] = 'Invalid IOC type. Must be ip, domain, url, or hash.';
        }
        if ($this->confidenceScore !== null && ($this->confidenceScore < 0 || $this->confidenceScore > 100)) {
            $errors[] = 'Confidence score must be between 0 and 100.';
        }
        return $errors;
    }

    public function isExpired(): bool
    {
        if (!$this->expiryAt) return false;
        return strtotime($this->expiryAt) < time();
    }

    public function getTagsArray(): array
    {
        return $this->tags ?? [];
    }
}
