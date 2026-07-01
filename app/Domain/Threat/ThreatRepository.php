<?php
/**
 * AegisZ Sentinel - Threat Repository (v0.6.0)
 * Extended with search/paginate, CVE filter, linked record queries.
 */

namespace App\Domain\Threat;

use App\Core\Database;
use App\Core\SearchQuery;
use App\Core\Paginator;
use PDO;

class ThreatRepository implements ThreatRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(ThreatEntity $threat): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO threats
             (title, description, source_feed, severity, mitre_technique, raw_data, cve_id, affected_systems)
             VALUES
             (:title, :description, :source_feed, :severity, :mitre_technique, :raw_data, :cve_id, :affected_systems)"
        );
        $stmt->execute([
            'title'            => $threat->title,
            'description'      => $threat->description,
            'source_feed'      => $threat->sourceFeed,
            'severity'         => $threat->severity,
            'mitre_technique'  => $threat->mitreTechnique,
            'raw_data'         => $threat->rawData ? json_encode($threat->rawData) : null,
            'cve_id'           => $threat->cveId,
            'affected_systems' => $threat->affectedSystems,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?ThreatEntity
    {
        $stmt = $this->db->prepare("SELECT * FROM threats WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? ThreatEntity::fromArray($data) : null;
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $sql    = "SELECT * FROM threats WHERE 1=1";
        $params = [];
        foreach (['severity','source_feed','mitre_technique'] as $f) {
            if (!empty($filters[$f])) {
                $sql .= " AND {$f} = :{$f}";
                $params[$f] = $filters[$f];
            }
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($r) => ThreatEntity::fromArray($r), $stmt->fetchAll());
    }

    public function search(SearchQuery $q): array
    {
        $allowed = ['created_at','title','severity','source_feed'];
        $sort    = in_array($q->sort, $allowed) ? $q->sort : 'created_at';
        $params  = [];
        $where   = "WHERE 1=1";

        if ($q->hasSearch()) {
            $where .= " AND (title LIKE :search OR description LIKE :search2
                          OR cve_id LIKE :search3)";
            $term = '%' . $q->search . '%';
            $params['search']  = $term;
            $params['search2'] = $term;
            $params['search3'] = $term;
        }
        foreach (['severity','source_feed','mitre_technique'] as $f) {
            if ($q->filter($f) !== '') {
                $where .= " AND {$f} = :{$f}";
                $params[$f] = $q->filter($f);
            }
        }
        if ($q->filter('date_from') !== '') {
            $where .= " AND created_at >= :date_from";
            $params['date_from'] = $q->filter('date_from') . ' 00:00:00';
        }
        if ($q->filter('date_to') !== '') {
            $where .= " AND created_at <= :date_to";
            $params['date_to'] = $q->filter('date_to') . ' 23:59:59';
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM threats {$where}");
        $countStmt->execute($params);
        $total     = (int) $countStmt->fetchColumn();
        $paginator = new Paginator($total, $q->page, $q->limit);

        $dataStmt = $this->db->prepare(
            "SELECT * FROM threats {$where}
             ORDER BY {$sort} {$q->direction}
             LIMIT {$q->limit} OFFSET {$paginator->offset}"
        );
        $dataStmt->execute($params);
        return [
            'data'      => array_map(fn($r) => ThreatEntity::fromArray($r), $dataStmt->fetchAll()),
            'paginator' => $paginator,
        ];
    }

    public function update(ThreatEntity $threat): bool
    {
        if ($threat->id === null) return false;
        $stmt = $this->db->prepare(
            "UPDATE threats SET title = :title, description = :description,
             source_feed = :source_feed, severity = :severity,
             mitre_technique = :mitre_technique, raw_data = :raw_data,
             cve_id = :cve_id, affected_systems = :affected_systems
             WHERE id = :id"
        );
        return $stmt->execute([
            'id'               => $threat->id,
            'title'            => $threat->title,
            'description'      => $threat->description,
            'source_feed'      => $threat->sourceFeed,
            'severity'         => $threat->severity,
            'mitre_technique'  => $threat->mitreTechnique,
            'raw_data'         => $threat->rawData ? json_encode($threat->rawData) : null,
            'cve_id'           => $threat->cveId,
            'affected_systems' => $threat->affectedSystems,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM threats WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM threats")->fetchColumn();
    }

    public function countBySeverity(): array
    {
        $stmt = $this->db->query("SELECT severity, COUNT(*) as count FROM threats GROUP BY severity");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // ---- Linked records ----

    public function getLinkedIOCs(int $threatId, int $limit = 10): array
    {
        // IOCs that share the same source feed as this threat
        $threat = $this->findById($threatId);
        if (!$threat || !$threat->sourceFeed) return [];
        $stmt = $this->db->prepare(
            "SELECT * FROM iocs WHERE source = :src ORDER BY confidence_score DESC LIMIT {$limit}"
        );
        $stmt->execute(['src' => $threat->sourceFeed]);
        return $stmt->fetchAll();
    }

    public function getLinkedCorrelations(int $threatId, int $limit = 10): array
    {
        $threat = $this->findById($threatId);
        if (!$threat) return [];
        $stmt = $this->db->prepare(
            "SELECT * FROM correlations WHERE source_feed = :src ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['src' => $threat->sourceFeed]);
        return $stmt->fetchAll();
    }

    public function getDistinctSources(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT source_feed FROM threats WHERE source_feed IS NOT NULL ORDER BY source_feed"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getDistinctMitreTechniques(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT mitre_technique FROM threats
             WHERE mitre_technique IS NOT NULL AND mitre_technique != ''
             ORDER BY mitre_technique"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getLatest(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM threats ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
