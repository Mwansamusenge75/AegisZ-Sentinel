<?php
/**
 * AegisZ Sentinel - System Logs Page
 */
?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">System Logs</h1>
        <p class="text-gray-400 text-sm mt-1">Audit trail and system events</p>
    </div>

    <div class="bg-aegisz-panel border border-gray-700 rounded-lg overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-white font-medium">Audit Log Entries</span>
                <span class="bg-gray-700 text-gray-300 text-xs px-2 py-0.5 rounded"><?= number_format($totalLogs) ?></span>
            </div>
            <div class="flex gap-2">
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-aegisz-success"></span> Info
                </span>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-aegisz-warning"></span> Warning
                </span>
                <span class="text-xs text-gray-400 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-aegisz-danger"></span> Error
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-800 text-gray-400 uppercase text-xs">
                    <tr>
                        <th class="px-5 py-3 font-medium">ID</th>
                        <th class="px-5 py-3 font-medium">Level</th>
                        <th class="px-5 py-3 font-medium">Message</th>
                        <th class="px-5 py-3 font-medium">Source</th>
                        <th class="px-5 py-3 font-medium">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500">No log entries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <?php
                                $level = $log['level'] ?? 'info';
                                if ($level === 'error') {
                                    $badgeClass = 'red-900/50 text-red-400';
                                } elseif ($level === 'warning') {
                                    $badgeClass = 'yellow-900/50 text-yellow-400';
                                } else {
                                    $badgeClass = 'green-900/50 text-green-400';
                                }
                            ?>
                            <tr class="hover:bg-gray-800/50 transition-colors">
                                <td class="px-5 py-3 text-gray-400">#<?= $log['id'] ?></td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-medium bg-<?= $badgeClass ?>">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        <?= \App\Core\Security::e(ucfirst($level)) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-300"><?= \App\Core\Security::e($log['message'] ?? '') ?></td>
                                <td class="px-5 py-3 text-gray-400"><?= \App\Core\Security::e($log['source'] ?? 'system') ?></td>
                                <td class="px-5 py-3 text-gray-400 whitespace-nowrap"><?= \App\Core\Security::e($log['created_at'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
