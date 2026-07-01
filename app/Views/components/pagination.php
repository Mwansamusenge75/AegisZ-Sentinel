<?php
/**
 * AegisZ Sentinel - Pagination Component (v0.6.0)
 * Receives: $paginator (Paginator object), $q (SearchQuery object)
 */
use App\Core\Security;
?>
<?php if ($paginator->pages > 1): ?>
    <div class="flex items-center justify-between mt-4 px-1">
        <div class="text-xs text-gray-500">
            Showing <?= $paginator->from ?>–<?= $paginator->to ?> of <?= number_format($paginator->total) ?>
        </div>
        <div class="flex items-center gap-1">
            <?php if ($paginator->hasPrev): ?>
                <a href="?<?= Security::e($q->toQueryString(['page' => $paginator->current - 1])) ?>"
                   class="px-3 py-1.5 text-xs bg-gray-800 border border-gray-700 rounded text-gray-300 hover:text-white hover:border-aegisz-accent transition-colors">
                    ← Prev
                </a>
            <?php endif; ?>

            <?php
                $start = max(1, $paginator->current - 2);
                $end   = min($paginator->pages, $paginator->current + 2);
            ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="?<?= Security::e($q->toQueryString(['page' => $p])) ?>"
                   class="px-3 py-1.5 text-xs rounded border transition-colors
                          <?= $p === $paginator->current
                              ? 'bg-aegisz-accent border-aegisz-accent text-white'
                              : 'bg-gray-800 border-gray-700 text-gray-300 hover:text-white hover:border-aegisz-accent' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>

            <?php if ($paginator->hasNext): ?>
                <a href="?<?= Security::e($q->toQueryString(['page' => $paginator->current + 1])) ?>"
                   class="px-3 py-1.5 text-xs bg-gray-800 border border-gray-700 rounded text-gray-300 hover:text-white hover:border-aegisz-accent transition-colors">
                    Next →
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="mt-2 text-xs text-gray-600 text-right">
        <?= number_format($paginator->total) ?> record(s)
    </div>
<?php endif; ?>
