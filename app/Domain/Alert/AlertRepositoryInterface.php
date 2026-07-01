<?php
/**
 * AegisZ Sentinel - Alert Repository Interface
 */

namespace App\Domain\Alert;

interface AlertRepositoryInterface
{
    public function create(AlertEntity $alert): int;
    public function findById(int $id): ?AlertEntity;
    public function findAll(array $filters = [], int $limit = 100): array;
    public function update(AlertEntity $alert): bool;
    public function delete(int $id): bool;
    public function count(): int;
    public function countByStatus(): array;
}
