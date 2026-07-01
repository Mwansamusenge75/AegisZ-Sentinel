<?php
/**
 * AegisZ Sentinel - IOC Service
 * Data validation + repository coordination. NO intelligence logic.
 */

namespace App\Domain\IOC;

use App\Core\Logger;

class IOCService
{
    private IOCRepositoryInterface $repository;
    private Logger $logger;

    public function __construct()
    {
        $this->repository = new IOCRepository();
        $this->logger = new Logger();
    }

    public function create(array $data): array
    {
        $entity = IOCEntity::fromArray($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repository->create($entity);
        $this->logger->info("IOC created: {$entity->value}", ['id' => $id, 'type' => $entity->type]);

        return ['success' => true, 'id' => $id];
    }

    public function findById(int $id): ?IOCEntity
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
            return ['success' => false, 'errors' => ['IOC not found.']];
        }

        $merged = array_merge($entity->toArray(), $data);
        $entity = IOCEntity::fromArray($merged);
        $entity->id = $id;

        $errors = $entity->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->repository->update($entity);
        $this->logger->info("IOC updated: {$entity->value}", ['id' => $id]);

        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $entity = $this->repository->findById($id);
        if (!$entity) {
            return ['success' => false, 'errors' => ['IOC not found.']];
        }

        $this->repository->delete($id);
        $this->logger->info("IOC deleted", ['id' => $id]);

        return ['success' => true];
    }

    public function getCounts(): array
    {
        return [
            'total' => $this->repository->count(),
            'by_type' => $this->repository->countByType(),
        ];
    }
}
