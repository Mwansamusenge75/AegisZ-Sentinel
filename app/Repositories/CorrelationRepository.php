<?php
/**
 * AegisZ Sentinel - Correlation Repository (v0.6.0)
 * SQL for the correlations table — search, filter, detail lookups.
 * All SQL lives here. No SQL in services or controllers.
 */

namespace App\Repositories;

use App\Core\Database;
use App\Core\SearchQuery;
use App\Core\Paginator;
use PDO;

class CorrelationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM correlations WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function search(SearchQuery $q): array
    {
        $allowed = ['created_at','confidence','severity','correlation_type'];
        $sort    = in_array($q->sort, $allowed) ? $q->sort : 'created_at';
        $params  = [];
        $where   = "WHERE 1=1";

        if ($q->hasSearch()) {
            $where .= " AND (ioc_value LIKE :search OR asset_name LIKE :search2
                          OR explanation LIKE :search3 OR source_feed LIKE :search4)";
            $term = '%' . $q->search . '%';
            $params['search']  = $term;
            $params['search2'] = $term;
            $params['search3'] = $term;
            $params['search4'] = $term;
        }
        foreach (['severity','correlation_type'] as $f) {
            if ($q->filter($f) !== '') {
                $where .= " AND {$f} = :{$f}";
                $params[$f] = $q->filter($f);
            }
        }
        if ($q->filter('confidence_min') !== '') {
            $where .= " AND confidence >= :confidence_min";
            $params['confidence_min'] = (int) $q->filter('confidence_min');
        }
        if ($q->filter('asset_name') !== '') {
            $where .= " AND asset_name LIKE :asset_name";
            $params['asset_name'] = '%' . $q->filter('asset_name') . '%';
        }
        if ($q->filter('date_from') !== '') {
            $where .= " AND created_at >= :date_from";
            $params['date_from'] = $q->filter('date_from') . ' 00:00:00';
        }
        if ($q->filter('date_to') !== '') {
            $where .= " AND created_at <= :date_to";
            $params['date_to'] = $q->filter('date_to') . ' 23:59:59';
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM correlations {$where}");
        $countStmt->execute($params);
        $total     = (int) $countStmt->fetchColumn();
        $paginator = new Paginator($total, $q->page, $q->limit);

        $dataStmt = $this->db->prepare(
            "SELECT * FROM correlations {$where}
             ORDER BY {$sort} {$q->direction}
             LIMIT {$q->limit} OFFSET {$paginator->offset}"
        );
        $dataStmt->execute($params);

        return ['data' => $dataStmt->fetchAll(), 'paginator' => $paginator];
    }

    public function getRecent(int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM correlations ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countBySeverity(): array
    {
        $stmt = $this->db->query(
            "SELECT severity, COUNT(*) AS count FROM correlations GROUP BY severity"
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function countByType(): array
    {
        $stmt = $this->db->query(
            "SELECT correlation_type, COUNT(*) AS count FROM correlations GROUP BY correlation_type"
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getLinkedIOC(int $iocId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM iocs WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $iocId]);
        return $stmt->fetch() ?: null;
    }

    public function getLinkedAsset(int $assetId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetch() ?: null;
    }

    public function getLinkedAlert(int $alertId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM alerts WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $alertId]);
        return $stmt->fetch() ?: null;
    }

    public function getLinkedIncident(int $incidentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM incidents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $incidentId]);
        return $stmt->fetch() ?: null;
    }
}
