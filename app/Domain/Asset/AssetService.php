<?php
/**
 * AegisZ Sentinel - Asset Service
 * Data validation + repository coordination. NO intelligence logic.
 */

namespace App\Domain\Asset;

use App\Core\Logger;

class AssetService
{
    private AssetRepositoryInterface $repository;
    private Logger $logger;

    public function __construct()
    {
        $this->repository = new AssetRepository();
        $this->logger = new Logger();
    }

    public function create(array $data): array
    {
        $entity = AssetEntity::fromArray($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repository->create($entity);
        $this->logger->info("Asset created: {$entity->name}", ['id' => $id]);

        return ['success' => true, 'id' => $id];
    }

    public function findById(int $id): ?AssetEntity
    {
        return $this->repository->findById($id);
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        return $this->repository->findAll($filters, $limit);
    }

    public function update(int $id, array $data): array
    {
        $entity = $this->repository->findById($id);
        if (!$entity) {
            return ['success' => false, 'errors' => ['Asset not found.']];
        }

        // Merge existing with new data
        $merged = array_merge($entity->toArray(), $data);
        $entity = AssetEntity::fromArray($merged);
        $entity->id = $id;

        $errors = $entity->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->repository->update($entity);
        $this->logger->info("Asset updated: {$entity->name}", ['id' => $id]);

        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $entity = $this->repository->findById($id);
        if (!$entity) {
            return ['success' => false, 'errors' => ['Asset not found.']];
        }

        $this->repository->delete($id);
        $this->logger->info("Asset deleted", ['id' => $id]);

        return ['success' => true];
    }

    public function getCounts(): array
    {
        return [
            'total' => $this->repository->count(),
            'by_criticality' => $this->repository->countByCriticality(),
        ];
    }
}
