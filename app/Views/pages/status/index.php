<?php
/**
 * AegisZ Sentinel - System Status Page
 */
?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">System Health</h1>
        <p class="text-gray-400 text-sm mt-1">Platform component status and resource monitoring</p>
    </div>

    <!-- Overall Health -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-gray-400 text-sm mb-1">Overall System Health</div>
                <div class="flex items-center gap-3">
                    <span class="w-4 h-4 rounded-full bg-<?= $health['color'] ?>-500"></span>
                    <span class="text-3xl font-bold text-white"><?= \App\Core\Security::e($health['label']) ?></span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-gray-400 text-sm">Uptime</div>
                <div class="text-xl font-semibold text-white"><?= \App\Core\Security::e($health['uptime']) ?></div>
            </div>
        </div>
    </div>

    <!-- Components Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($components as $component): ?>
            <?php
                $compStatus = $component['status'];
                if ($compStatus === 'operational') {
                    $compColor = 'aegisz-success';
                } elseif ($compStatus === 'warning') {
                    $compColor = 'aegisz-warning';
                } elseif ($compStatus === 'down') {
                    $compColor = 'aegisz-danger';
                } else {
                    $compColor = 'gray-500';
                }
            ?>
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5 flex items-center justify-between">
                <div>
                    <div class="text-white font-medium"><?= \App\Core\Security::e($component['name']) ?></div>
                    <div class="text-sm text-gray-400 mt-1"><?= \App\Core\Security::e($component['label']) ?></div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-<?= $compColor ?>"></span>
                    <span class="text-sm text-gray-300 capitalize"><?= \App\Core\Security::e($component['status']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Resource Usage -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h3 class="text-white font-medium mb-4">Memory Usage</h3>
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm"><?= \App\Core\Security::e($health['memory']['used']) ?> / <?= \App\Core\Security::e($health['memory']['limit']) ?></span>
                <span class="text-white font-medium"><?= $health['memory']['percent'] ?>%</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2">
                <div class="bg-aegisz-accent h-2 rounded-full transition-all" style="width: <?= $health['memory']['percent'] ?>%"></div>
            </div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h3 class="text-white font-medium mb-4">Disk Usage</h3>
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm"><?= \App\Core\Security::e($health['disk']['used']) ?> / <?= \App\Core\Security::e($health['disk']['total']) ?></span>
                <span class="text-white font-medium"><?= $health['disk']['percent'] ?>%</span>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2">
                <div class="bg-aegisz-success h-2 rounded-full transition-all" style="width: <?= $health['disk']['percent'] ?>%"></div>
            </div>
        </div>
    </div>

    <div class="mt-6 text-xs text-gray-500 text-right">
        Last checked: <?= \App\Core\Security::e($timestamp) ?>
    </div>
</div>
