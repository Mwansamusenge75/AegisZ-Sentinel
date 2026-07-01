<?php
/**
 * AegisZ Sentinel - IOC Repository Interface
 */

namespace App\Domain\IOC;

interface IOCRepositoryInterface
{
    public function create(IOCEntity $ioc): int;
    public function findById(int $id): ?IOCEntity;
    public function findAll(array $filters = [], int $limit = 100): array;
    public function findByValue(string $value): ?IOCEntity;
    public function update(IOCEntity $ioc): bool;
    public function delete(int $id): bool;
    public function count(): int;
    public function countByType(): array;
}
