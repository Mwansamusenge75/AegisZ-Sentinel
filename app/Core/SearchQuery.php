<?php
/**
 * AegisZ Sentinel - SearchQuery (v0.6.0)
 * Reusable value object encapsulating all search/filter/sort/pagination parameters.
 * Built from $_GET by controllers. Passed into repository search methods.
 * No SQL. No HTTP. Pure value object.
 */

namespace App\Core;

class SearchQuery
{
    public readonly int    $page;
    public readonly int    $limit;
    public readonly string $sort;
    public readonly string $direction;
    public readonly string $search;
    public readonly array  $filters;

    private const MAX_LIMIT     = 100;
    private const DEFAULT_LIMIT = 25;

    public function __construct(
        int    $page      = 1,
        int    $limit     = self::DEFAULT_LIMIT,
        string $sort      = 'created_at',
        string $direction = 'desc',
        string $search    = '',
        array  $filters   = []
    ) {
        $this->page      = max(1, $page);
        $this->limit     = min(max(1, $limit), self::MAX_LIMIT);
        $this->sort      = $this->sanitiseSort($sort);
        $this->direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';
        $this->search    = trim($search);
        $this->filters   = $this->sanitiseFilters($filters);
    }

    /**
     * Build a SearchQuery from $_GET parameters.
     * Allowed sort columns must be passed in to prevent SQL injection.
     */
    public static function fromRequest(array $get, array $allowedSorts = ['created_at']): self
    {
        $sort = in_array($get['sort'] ?? '', $allowedSorts) ? $get['sort'] : $allowedSorts[0];

        return new self(
            page:      (int) ($get['page'] ?? 1),
            limit:     (int) ($get['limit'] ?? self::DEFAULT_LIMIT),
            sort:      $sort,
            direction: $get['dir'] ?? 'desc',
            search:    Security::sanitize($get['q'] ?? ''),
            filters:   array_map(
                fn($v) => Security::sanitize((string) $v),
                array_filter($get, fn($k) => !in_array($k, ['page', 'limit', 'sort', 'dir', 'q']), ARRAY_FILTER_USE_KEY)
            )
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    public function hasSearch(): bool
    {
        return $this->search !== '';
    }

    public function filter(string $key, mixed $default = ''): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    private function sanitiseSort(string $sort): string
    {
        // Strip anything not alphanumeric or underscore — belt-and-suspenders
        return preg_replace('/[^a-zA-Z0-9_]/', '', $sort) ?: 'created_at';
    }

    private function sanitiseFilters(array $filters): array
    {
        $clean = [];
        foreach ($filters as $k => $v) {
            $k = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            if ($k !== '') {
                $clean[$k] = is_string($v) ? trim($v) : $v;
            }
        }
        return $clean;
    }

    /**
     * Build a query string for pagination links, preserving current filters.
     */
    public function toQueryString(array $overrides = []): string
    {
        $params = array_merge(
            ['q' => $this->search, 'sort' => $this->sort, 'dir' => strtolower($this->direction), 'limit' => $this->limit],
            $this->filters,
            $overrides
        );
        // Remove empty values
        $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
        return http_build_query($params);
    }
}
