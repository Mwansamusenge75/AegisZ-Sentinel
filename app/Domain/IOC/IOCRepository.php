<?php
/**
 * AegisZ Sentinel - IOC Repository (v0.6.0)
 * Extended with search/paginate, false positive, expiry, tags, history.
 */

namespace App\Domain\IOC;

use App\Core\Database;
use App\Core\SearchQuery;
use App\Core\Paginator;
use PDO;

class IOCRepository implements IOCRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(IOCEntity $ioc): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO iocs
             (type, value, source, confidence_score, first_seen, last_seen,
              raw_data, false_positive, expiry_at, tags)
             VALUES
             (:type, :value, :source, :confidence_score, :first_seen, :last_seen,
              :raw_data, :false_positive, :expiry_at, :tags)"
        );
        $stmt->execute([
            'type'             => $ioc->type,
            'value'            => $ioc->value,
            'source'           => $ioc->source,
            'confidence_score' => $ioc->confidenceScore,
            'first_seen'       => $ioc->firstSeen ?? date('Y-m-d H:i:s'),
            'last_seen'        => $ioc->lastSeen  ?? date('Y-m-d H:i:s'),
            'raw_data'         => $ioc->rawData ? json_encode($ioc->rawData) : null,
            'false_positive'   => $ioc->falsePositive ? 1 : 0,
            'expiry_at'        => $ioc->expiryAt,
            'tags'             => $ioc->tags ? json_encode($ioc->tags) : null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?IOCEntity
    {
        $stmt = $this->db->prepare("SELECT * FROM iocs WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? IOCEntity::fromArray($data) : null;
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $sql    = "SELECT * FROM iocs WHERE 1=1";
        $params = [];
        foreach (['type','source'] as $f) {
            if (!empty($filters[$f])) {
                $sql .= " AND {$f} = :{$f}";
                $params[$f] = $filters[$f];
            }
        }
        if (isset($filters['confidence_min'])) {
            $sql .= " AND confidence_score >= :confidence_min";
            $params['confidence_min'] = $filters['confidence_min'];
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($r) => IOCEntity::fromArray($r), $stmt->fetchAll());
    }

    public function search(SearchQuery $q): array
    {
        $allowed = ['created_at','value','confidence_score','type','last_seen'];
        $sort    = in_array($q->sort, $allowed) ? $q->sort : 'created_at';
        $params  = [];
        $where   = "WHERE 1=1";

        if ($q->hasSearch()) {
            $where .= " AND (value LIKE :search OR source LIKE :search2)";
            $term = '%' . $q->search . '%';
            $params['search']  = $term;
            $params['search2'] = $term;
        }
        foreach (['type','source'] as $f) {
            if ($q->filter($f) !== '') {
                $where .= " AND {$f} = :{$f}";
                $params[$f] = $q->filter($f);
            }
        }
        if ($q->filter('false_positive') !== '') {
            $where .= " AND false_positive = :false_positive";
            $params['false_positive'] = (int) $q->filter('false_positive');
        }
        if ($q->filter('confidence_min') !== '') {
            $where .= " AND confidence_score >= :confidence_min";
            $params['confidence_min'] = (int) $q->filter('confidence_min');
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM iocs {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $paginator = new Paginator($total, $q->page, $q->limit);

        $dataStmt = $this->db->prepare(
            "SELECT * FROM iocs {$where}
             ORDER BY {$sort} {$q->direction}
             LIMIT {$q->limit} OFFSET {$paginator->offset}"
        );
        $dataStmt->execute($params);
        return [
            'data'      => array_map(fn($r) => IOCEntity::fromArray($r), $dataStmt->fetchAll()),
            'paginator' => $paginator,
        ];
    }

    public function findByValue(string $value): ?IOCEntity
    {
        $stmt = $this->db->prepare("SELECT * FROM iocs WHERE value = :value LIMIT 1");
        $stmt->execute(['value' => $value]);
        $data = $stmt->fetch();
        return $data ? IOCEntity::fromArray($data) : null;
    }

    public function update(IOCEntity $ioc): bool
    {
        if ($ioc->id === null) return false;
        $stmt = $this->db->prepare(
            "UPDATE iocs SET type = :type, value = :value, source = :source,
             confidence_score = :confidence_score, first_seen = :first_seen,
             last_seen = :last_seen, raw_data = :raw_data,
             false_positive = :false_positive, expiry_at = :expiry_at, tags = :tags
             WHERE id = :id"
        );
        return $stmt->execute([
            'id'               => $ioc->id,
            'type'             => $ioc->type,
            'value'            => $ioc->value,
            'source'           => $ioc->source,
            'confidence_score' => $ioc->confidenceScore,
            'first_seen'       => $ioc->firstSeen,
            'last_seen'        => $ioc->lastSeen,
            'raw_data'         => $ioc->rawData ? json_encode($ioc->rawData) : null,
            'false_positive'   => $ioc->falsePositive ? 1 : 0,
            'expiry_at'        => $ioc->expiryAt,
            'tags'             => $ioc->tags ? json_encode($ioc->tags) : null,
        ]);
    }

    public function setFalsePositive(int $id, bool $value): bool
    {
        $stmt = $this->db->prepare("UPDATE iocs SET false_positive = :v WHERE id = :id");
        return $stmt->execute(['v' => $value ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM iocs WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM iocs")->fetchColumn();
    }

    public function countByType(): array
    {
        $stmt = $this->db->query("SELECT type, COUNT(*) as count FROM iocs GROUP BY type");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // ---- Linked records ----

    public function getLinkedCorrelations(int $iocId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM correlations WHERE ioc_id = :id ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['id' => $iocId]);
        return $stmt->fetchAll();
    }

    public function getLinkedAlerts(int $iocId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM alerts WHERE linked_ioc_id = :id ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['id' => $iocId]);
        return $stmt->fetchAll();
    }

    public function getLinkedThreats(string $iocValue, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT t.* FROM threats t
             INNER JOIN correlations c ON c.source_feed = t.source_feed
             INNER JOIN iocs i ON c.ioc_id = i.id
             WHERE i.value = :val
             ORDER BY t.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['val' => $iocValue]);
        return $stmt->fetchAll();
    }

    // ---- History ----

    public function addHistory(int $iocId, string $event, ?string $detail = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ioc_history (ioc_id, event, detail) VALUES (:ioc_id, :event, :detail)"
        );
        $stmt->execute(['ioc_id' => $iocId, 'event' => $event, 'detail' => $detail]);
    }

    public function getHistory(int $iocId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM ioc_history WHERE ioc_id = :id ORDER BY created_at DESC"
        );
        $stmt->execute(['id' => $iocId]);
        return $stmt->fetchAll();
    }

    public function getDistinctSources(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT source FROM iocs WHERE source IS NOT NULL ORDER BY source"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
