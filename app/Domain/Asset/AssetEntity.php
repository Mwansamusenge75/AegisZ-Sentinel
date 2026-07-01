<?php
/**
 * AegisZ Sentinel - Asset Entity (v0.6.0)
 * Extended with: operating_system, network_segment, notes.
 */

namespace App\Domain\Asset;

class AssetEntity
{
    public ?int    $id              = null;
    public string  $name            = '';
    public ?string $hostname        = null;
    public ?string $ipAddress       = null;
    public string  $assetType       = 'server';
    public ?string $department      = null;
    public ?string $owner           = null;
    public ?string $location        = null;
    public string  $criticality     = 'medium';
    public string  $status          = 'active';
    // v0.6.0 additions
    public ?string $operatingSystem = null;
    public ?string $networkSegment  = null;
    public ?string $notes           = null;
    // v0.7.0 additions — geographic fields for NCSAM map integration
    public ?float  $latitude        = null;
    public ?float  $longitude       = null;
    public ?string $province        = null;
    public ?string $district        = null;
    public ?string $locationName    = null;
    public ?string $createdAt       = null;
    public ?string $updatedAt       = null;

    public static function fromArray(array $data): self
    {
        $entity                  = new self();
        $entity->id              = isset($data['id']) ? (int) $data['id'] : null;
        $entity->name            = $data['name'] ?? '';
        $entity->hostname        = $data['hostname'] ?? null;
        $entity->ipAddress       = $data['ip_address'] ?? null;
        $entity->assetType       = $data['asset_type'] ?? 'server';
        $entity->department      = $data['department'] ?? null;
        $entity->owner           = $data['owner'] ?? null;
        $entity->location        = $data['location'] ?? null;
        $entity->criticality     = $data['criticality'] ?? 'medium';
        $entity->status          = $data['status'] ?? 'active';
        $entity->operatingSystem = $data['operating_system'] ?? null;
        $entity->networkSegment  = $data['network_segment'] ?? null;
        $entity->notes           = $data['notes'] ?? null;
        $entity->latitude        = isset($data['latitude']) && $data['latitude'] !== null ? (float) $data['latitude'] : null;
        $entity->longitude       = isset($data['longitude']) && $data['longitude'] !== null ? (float) $data['longitude'] : null;
        $entity->province        = $data['province'] ?? null;
        $entity->district        = $data['district'] ?? null;
        $entity->locationName    = $data['location_name'] ?? null;
        $entity->createdAt       = $data['created_at'] ?? null;
        $entity->updatedAt       = $data['updated_at'] ?? null;
        return $entity;
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'hostname'         => $this->hostname,
            'ip_address'       => $this->ipAddress,
            'asset_type'       => $this->assetType,
            'department'       => $this->department,
            'owner'            => $this->owner,
            'location'         => $this->location,
            'criticality'      => $this->criticality,
            'status'           => $this->status,
            'operating_system' => $this->operatingSystem,
            'network_segment'  => $this->networkSegment,
            'notes'            => $this->notes,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'province'         => $this->province,
            'district'         => $this->district,
            'location_name'    => $this->locationName,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
        ];
    }

    public function hasGeoLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function validate(): array
    {
        $errors = [];
        if (empty(trim($this->name))) {
            $errors[] = 'Asset name is required.';
        }
        if (!in_array($this->assetType, ['server', 'endpoint', 'router', 'app', 'db', 'website'])) {
            $errors[] = 'Invalid asset type.';
        }
        if (!in_array($this->criticality, ['low', 'medium', 'high', 'critical'])) {
            $errors[] = 'Invalid criticality level.';
        }
        if (!in_array($this->status, ['active', 'inactive', 'maintenance'])) {
            $errors[] = 'Invalid status.';
        }
        if ($this->ipAddress && !filter_var($this->ipAddress, FILTER_VALIDATE_IP)) {
            $errors[] = 'Invalid IP address format.';
        }
        if ($this->latitude !== null && ($this->latitude < -90 || $this->latitude > 90)) {
            $errors[] = 'Latitude must be between -90 and 90.';
        }
        if ($this->longitude !== null && ($this->longitude < -180 || $this->longitude > 180)) {
            $errors[] = 'Longitude must be between -180 and 180.';
        }
        return $errors;
    }
}
