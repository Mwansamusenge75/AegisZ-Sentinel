<?php
/**
 * AegisZ Sentinel - Paginator (v0.6.0)
 * Pure pagination math. No SQL. No HTTP.
 * Used by repositories to return pagination metadata alongside data.
 */

namespace App\Core;

class Paginator
{
    public readonly int  $total;
    public readonly int  $pages;
    public readonly int  $current;
    public readonly int  $limit;
    public readonly int  $offset;
    public readonly bool $hasNext;
    public readonly bool $hasPrev;
    public readonly int  $from;
    public readonly int  $to;

    public function __construct(int $total, int $page, int $limit)
    {
        $this->total   = max(0, $total);
        $this->limit   = max(1, $limit);
        $this->pages   = $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 1;
        $this->current = max(1, min($page, max(1, $this->pages)));
        $this->offset  = ($this->current - 1) * $this->limit;
        $this->hasNext = $this->current < $this->pages;
        $this->hasPrev = $this->current > 1;
        $this->from    = $this->total > 0 ? $this->offset + 1 : 0;
        $this->to      = min($this->total, $this->offset + $this->limit);
    }

    public function toArray(): array
    {
        return [
            'total'    => $this->total,
            'pages'    => $this->pages,
            'current'  => $this->current,
            'limit'    => $this->limit,
            'offset'   => $this->offset,
            'has_next' => $this->hasNext,
            'has_prev' => $this->hasPrev,
            'from'     => $this->from,
            'to'       => $this->to,
        ];
    }
}
