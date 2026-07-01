<?php
/**
 * AegisZ Sentinel - Alert Service
 * Data validation + repository coordination. NO alert engine logic.
 */

namespace App\Domain\Alert;

use App\Core\Logger;

class AlertService
{
    private AlertRepositoryInterface $repository;
    private Logger $logger;

    public function __construct()
    {
        $this->repository = new AlertRepository();
        $this->logger = new Logger();
    }

    public function create(array $data): array
    {
        $entity = AlertEntity::fromArray($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repository->create($entity);
        $this->logger->info("Alert created: {$entity->title}", ['id' => $id, 'severity' => $entity->severity]);

        return ['success' => true, 'id' => $id];
    }

    public function findById(int $id): ?AlertEntity
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
            return ['success' => false, 'errors' => ['Alert not found.']];
        }

        $merged = array_merge($entity->toArray(), $data);
        $entity = AlertEntity::fromArray($merged);
        $entity->id = $id;

        $errors = $entity->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->repository->update($entity);
        $this->logger->info("Alert updated: {$entity->title}", ['id' => $id]);

        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $entity = $this->repository->findById($id);
        if (!$entity) {
            return ['success' => false, 'errors' => ['Alert not found.']];
        }

        $this->repository->delete($id);
        $this->logger->info("Alert deleted", ['id' => $id]);

        return ['success' => true];
    }

    public function getCounts(): array
    {
        return [
            'total' => $this->repository->count(),
            'by_status' => $this->repository->countByStatus(),
        ];
    }
}
