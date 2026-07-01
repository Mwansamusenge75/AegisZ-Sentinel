<?php
/**
 * AegisZ Sentinel - Threat Repository Interface
 */

namespace App\Domain\Threat;

interface ThreatRepositoryInterface
{
    public function create(ThreatEntity $threat): int;
    public function findById(int $id): ?ThreatEntity;
    public function findAll(array $filters = [], int $limit = 100): array;
    public function update(ThreatEntity $threat): bool;
    public function delete(int $id): bool;
    public function count(): int;
    public function countBySeverity(): array;
}
