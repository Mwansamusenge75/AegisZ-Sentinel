<?php
/**
 * AegisZ Sentinel - Asset Repository (v0.6.0)
 * Extended with search/paginate, linked record queries, notes.
 * All SQL lives here. No SQL in services or controllers.
 */

namespace App\Domain\Asset;

use App\Core\Database;
use App\Core\SearchQuery;
use App\Core\Paginator;
use PDO;

class AssetRepository implements AssetRepositoryInterface
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(AssetEntity $asset): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO assets
             (name, hostname, ip_address, asset_type, department, owner,
              location, criticality, status, operating_system, network_segment, notes,
              latitude, longitude, province, district, location_name)
             VALUES
             (:name, :hostname, :ip_address, :asset_type, :department, :owner,
              :location, :criticality, :status, :operating_system, :network_segment, :notes,
              :latitude, :longitude, :province, :district, :location_name)"
        );
        $stmt->execute([
            'name'             => $asset->name,
            'hostname'         => $asset->hostname,
            'ip_address'       => $asset->ipAddress,
            'asset_type'       => $asset->assetType,
            'department'       => $asset->department,
            'owner'            => $asset->owner,
            'location'         => $asset->location,
            'criticality'      => $asset->criticality,
            'status'           => $asset->status,
            'operating_system' => $asset->operatingSystem,
            'network_segment'  => $asset->networkSegment,
            'notes'            => $asset->notes,
            'latitude'         => $asset->latitude,
            'longitude'        => $asset->longitude,
            'province'         => $asset->province,
            'district'         => $asset->district,
            'location_name'    => $asset->locationName,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?AssetEntity
    {
        $stmt = $this->db->prepare("SELECT * FROM assets WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        return $data ? AssetEntity::fromArray($data) : null;
    }

    public function findAll(array $filters = [], int $limit = 100): array
    {
        $sql    = "SELECT * FROM assets WHERE 1=1";
        $params = [];

        if (!empty($filters['asset_type'])) {
            $sql .= " AND asset_type = :asset_type";
            $params['asset_type'] = $filters['asset_type'];
        }
        if (!empty($filters['criticality'])) {
            $sql .= " AND criticality = :criticality";
            $params['criticality'] = $filters['criticality'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['location'])) {
            $sql .= " AND location = :location";
            $params['location'] = $filters['location'];
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($r) => AssetEntity::fromArray($r), $stmt->fetchAll());
    }

    /**
     * Full search with pagination — v0.6.0.
     * Returns ['data' => AssetEntity[], 'paginator' => Paginator]
     */
    public function search(SearchQuery $q): array
    {
        $allowed = ['name', 'created_at', 'criticality', 'ip_address', 'asset_type'];
        $sort    = in_array($q->sort, $allowed) ? $q->sort : 'created_at';
        $params  = [];
        $where   = "WHERE 1=1";

        if ($q->hasSearch()) {
            $where .= " AND (name LIKE :search OR hostname LIKE :search2
                         OR ip_address LIKE :search3 OR owner LIKE :search4
                         OR department LIKE :search5)";
            $term = '%' . $q->search . '%';
            $params['search']  = $term;
            $params['search2'] = $term;
            $params['search3'] = $term;
            $params['search4'] = $term;
            $params['search5'] = $term;
        }
        foreach (['asset_type','criticality','status','department','location','network_segment'] as $f) {
            if ($q->filter($f) !== '') {
                $where .= " AND {$f} = :{$f}";
                $params[$f] = $q->filter($f);
            }
        }

        // Count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM assets {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $paginator = new Paginator($total, $q->page, $q->limit);

        $dataStmt = $this->db->prepare(
            "SELECT * FROM assets {$where}
             ORDER BY {$sort} {$q->direction}
             LIMIT {$q->limit} OFFSET {$paginator->offset}"
        );
        $dataStmt->execute($params);
        $data = array_map(fn($r) => AssetEntity::fromArray($r), $dataStmt->fetchAll());

        return ['data' => $data, 'paginator' => $paginator];
    }

    public function update(AssetEntity $asset): bool
    {
        if ($asset->id === null) return false;
        $stmt = $this->db->prepare(
            "UPDATE assets SET
             name = :name, hostname = :hostname, ip_address = :ip_address,
             asset_type = :asset_type, department = :department, owner = :owner,
             location = :location, criticality = :criticality, status = :status,
             operating_system = :operating_system, network_segment = :network_segment,
             notes = :notes,
             latitude = :latitude, longitude = :longitude, province = :province,
             district = :district, location_name = :location_name
             WHERE id = :id"
        );
        return $stmt->execute([
            'id'               => $asset->id,
            'name'             => $asset->name,
            'hostname'         => $asset->hostname,
            'ip_address'       => $asset->ipAddress,
            'asset_type'       => $asset->assetType,
            'department'       => $asset->department,
            'owner'            => $asset->owner,
            'location'         => $asset->location,
            'criticality'      => $asset->criticality,
            'status'           => $asset->status,
            'operating_system' => $asset->operatingSystem,
            'network_segment'  => $asset->networkSegment,
            'notes'            => $asset->notes,
            'latitude'         => $asset->latitude,
            'longitude'        => $asset->longitude,
            'province'         => $asset->province,
            'district'         => $asset->district,
            'location_name'    => $asset->locationName,
        ]);
    }

    /**
     * Set just the geo-location fields for an asset (used by the
     * "Set location on map" UI — no paid geocoding API, manual pin only).
     */
    public function setLocation(int $assetId, float $lat, float $lng, ?string $province, ?string $district, ?string $locationName): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE assets SET latitude = :lat, longitude = :lng, province = :province,
             district = :district, location_name = :location_name WHERE id = :id"
        );
        return $stmt->execute([
            'id'            => $assetId,
            'lat'           => $lat,
            'lng'           => $lng,
            'province'      => $province,
            'district'      => $district,
            'location_name' => $locationName,
        ]);
    }

    /**
     * Fetch all assets that have a geo-location set, for map rendering.
     */
    public function findAllWithLocation(array $filters = []): array
    {
        $sql    = "SELECT * FROM assets WHERE latitude IS NOT NULL AND longitude IS NOT NULL";
        $params = [];
        foreach (['criticality', 'asset_type', 'status', 'province'] as $f) {
            if (!empty($filters[$f])) {
                $sql .= " AND {$f} = :{$f}";
                $params[$f] = $filters[$f];
            }
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(fn($r) => AssetEntity::fromArray($r), $stmt->fetchAll());
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM assets WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM assets")->fetchColumn();
    }

    public function countByCriticality(): array
    {
        $stmt = $this->db->query("SELECT criticality, COUNT(*) as count FROM assets GROUP BY criticality");
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // ---- Linked record queries (for detail page) ----

    public function getLinkedAlerts(int $assetId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM alerts WHERE linked_asset_id = :id ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function getLinkedIncidents(int $assetId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM incidents WHERE linked_asset_id = :id ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function getLinkedCorrelations(int $assetId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM correlations WHERE asset_id = :id ORDER BY created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function getLinkedThreats(int $assetId, int $limit = 10): array
    {
        // Threats linked via correlations → threat title stored in source_feed context
        $stmt = $this->db->prepare(
            "SELECT DISTINCT t.* FROM threats t
             INNER JOIN correlations c ON c.source_feed = t.source_feed
             WHERE c.asset_id = :id
             ORDER BY t.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function getCriticalWithCorrelations(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, COUNT(c.id) AS correlation_count
             FROM assets a
             INNER JOIN correlations c ON c.asset_id = a.id
             WHERE a.criticality = 'critical'
             GROUP BY a.id
             ORDER BY correlation_count DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ---- Asset Notes ----

    public function addNote(int $assetId, int $userId, string $note): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO asset_notes (asset_id, user_id, note) VALUES (:asset_id, :user_id, :note)"
        );
        $stmt->execute(['asset_id' => $assetId, 'user_id' => $userId, 'note' => $note]);
        return (int) $this->db->lastInsertId();
    }

    public function getNotes(int $assetId): array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, u.username FROM asset_notes n
             LEFT JOIN users u ON n.user_id = u.id
             WHERE n.asset_id = :id ORDER BY n.created_at DESC"
        );
        $stmt->execute(['id' => $assetId]);
        return $stmt->fetchAll();
    }

    public function getDistinctDepartments(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT department FROM assets WHERE department IS NOT NULL ORDER BY department"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getDistinctLocations(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT location FROM assets WHERE location IS NOT NULL ORDER BY location"
        );
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
