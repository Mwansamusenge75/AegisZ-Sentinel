<?php
/**
 * AegisZ Sentinel - Incident Service
 * Data validation + repository coordination. NO incident response logic.
 */

namespace App\Domain\Incident;

use App\Core\Logger;

class IncidentService
{
    private IncidentRepositoryInterface $repository;
    private Logger $logger;

    public function __construct()
    {
        $this->repository = new IncidentRepository();
        $this->logger = new Logger();
    }

    public function create(array $data): array
    {
        $entity = IncidentEntity::fromArray($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repository->create($entity);
        $this->logger->info("Incident created: {$entity->title}", ['id' => $id, 'severity' => $entity->severity]);

        return ['success' => true, 'id' => $id];
    }

    public function findById(int $id): ?IncidentEntity
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
            return ['success' => false, 'errors' => ['Incident not found.']];
        }

        $merged = array_merge($entity->toArray(), $data);
        $entity = IncidentEntity::fromArray($merged);
        $entity->id = $id;

        $errors = $entity->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $this->repository->update($entity);
        $this->logger->info("Incident updated: {$entity->title}", ['id' => $id]);

        return ['success' => true];
    }

    public function delete(int $id): array
    {
        $entity = $this->repository->findById($id);
        if (!$entity) {
            return ['success' => false, 'errors' => ['Incident not found.']];
        }

        $this->repository->delete($id);
        $this->logger->info("Incident deleted", ['id' => $id]);

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
