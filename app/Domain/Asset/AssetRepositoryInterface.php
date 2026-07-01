<?php
/**
 * AegisZ Sentinel - Asset Repository Interface
 */

namespace App\Domain\Asset;

interface AssetRepositoryInterface
{
    public function create(AssetEntity $asset): int;
    public function findById(int $id): ?AssetEntity;
    public function findAll(array $filters = [], int $limit = 100): array;
    public function update(AssetEntity $asset): bool;
    public function delete(int $id): bool;
    public function count(): int;
    public function countByCriticality(): array;
}
