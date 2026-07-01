<?php
/**
 * AegisZ Sentinel - Threat Service
 * Data validation + repository coordination. NO intelligence logic.
 */

namespace App\Domain\Threat;

use App\Core\Logger;

class ThreatService
{
    private ThreatRepositoryInterface $repository;
    private Logger $logger;

    public function __construct()
    {
        $this->repository = new ThreatRepository();
        $this->logger = new Logger();
    }

    public function create(array $data): array
    {
        $entity = ThreatEntity::fromArray($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repository->create($entity);
        $this->logger->info("Threat created: {$entity->title}", ['id' => $id]);

        return ['success' => true, 'id' => $id];
    }

    public function findById(int $id): ?ThreatEntity
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
            return ['success' => false, 'errors' => ['Threat not found.']];
        }

        $merged = array_merge($entity->toArray(), $data);
        $entity = ThreatEntity::fromArray($merged);
        $entity->id = $id;

        $errors = $entity->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->repository->update($entity);
        $this->logger->info("Threat updated: {$entity->title}", ['id' => $id]);

        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $entity = $this->repository->findById($id);
        if (!$entity) {
            return ['success' => false, 'errors' => ['Threat not found.']];
        }

        $this->repository->delete($id);
        $this->logger->info("Threat deleted", ['id' => $id]);

        return ['success' => true];
    }

    public function getCounts(): array
    {
        return [
            'total' => $this->repository->count(),
            'by_severity' => $this->repository->countBySeverity(),
        ];
    }
}
