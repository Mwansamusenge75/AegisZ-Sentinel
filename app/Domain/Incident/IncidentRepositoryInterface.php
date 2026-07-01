<?php
/**
 * AegisZ Sentinel - Incident Repository Interface
 */

namespace App\Domain\Incident;

interface IncidentRepositoryInterface
{
    public function create(IncidentEntity $incident): int;
    public function findById(int $id): ?IncidentEntity;
    public function findAll(array $filters = [], int $limit = 100): array;
    public function update(IncidentEntity $incident): bool;
    public function delete(int $id): bool;
    public function count(): int;
    public function countByStatus(): array;
}
