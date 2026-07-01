<?php
/**
 * AegisZ Sentinel - Timeline Component (v0.6.0)
 * Reusable timeline. Receives $timeline array of events.
 * Each entry: ['timestamp', 'event', 'detail'(optional), 'color'(optional: accent|success|warning|danger|gray)]
 */
use App\Core\Security;
?>
<div class="space-y-0">
    <?php if (empty($timeline)): ?>
        <p class="text-gray-500 text-sm text-center py-4">No timeline events recorded.</p>
    <?php else: ?>
        <?php foreach ($timeline as $i => $entry): ?>
            <?php
                $color = $entry['color'] ?? 'aegisz-accent';
                $colorMap = [
                    'accent'  => 'aegisz-accent',
                    'success' => 'aegisz-success',
                    'warning' => 'aegisz-warning',
                    'danger'  => 'aegisz-danger',
                    'gray'    => 'gray',
                ];
                $c = $colorMap[$color] ?? $color;
                $isLast = $i === count($timeline) - 1;
            ?>
            <div class="flex gap-3">
                <div class="flex flex-col items-center">
                    <div class="w-2.5 h-2.5 rounded-full bg-<?= $c ?>-500 mt-1 shrink-0"></div>
                    <?php if (!$isLast): ?>
                        <div class="w-px flex-1 bg-gray-700 my-1"></div>
                    <?php endif; ?>
                </div>
                <div class="pb-4 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm text-white font-medium"><?= Security::e($entry['event']) ?></span>
                        <?php if (!empty($entry['actor'])): ?>
                            <span class="text-xs text-gray-500">by <?= Security::e($entry['actor']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($entry['detail'])): ?>
                        <div class="text-xs text-gray-400 mt-0.5"><?= Security::e($entry['detail']) ?></div>
                    <?php endif; ?>
                    <div class="text-xs text-gray-600 mt-0.5"><?= Security::e($entry['timestamp'] ?? '') ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
