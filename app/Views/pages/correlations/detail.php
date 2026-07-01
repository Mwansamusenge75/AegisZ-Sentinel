<?php use App\Core\Security; ?>
<div class="max-w-5xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/correlations" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Correlations</a>

    <?php
        $sevColor = ['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$corr['severity']]??'gray';
        $typeLabel = str_replace('_',' → ',strtoupper($corr['correlation_type']??''));
    ?>
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2 flex-wrap">
            <span class="text-xs font-mono text-aegisz-accent bg-aegisz-accent/10 px-2 py-0.5 rounded"><?= Security::e($typeLabel) ?></span>
            <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400"><?= Security::e(ucfirst($corr['severity'])) ?></span>
            <span class="text-xs text-gray-500">Confidence: <?= (int)$corr['confidence'] ?>%</span>
        </div>
        <h1 class="text-xl font-bold text-white">Correlation #<?= (int)$corr['id'] ?></h1>
        <p class="text-gray-500 text-sm mt-1"><?= Security::e($corr['created_at']) ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <!-- Explanation -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Explanation
                </h2>
                <p class="text-gray-300 text-sm leading-relaxed"><?= Security::e($corr['explanation']) ?></p>
            </div>

            <!-- Linked Records -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">Linked Records</h2>
                <div class="space-y-3">
                    <?php if ($linkedIOC): ?>
                        <div class="p-3 bg-gray-800/50 rounded-lg border-l-2 border-aegisz-accent-600">
                            <div class="text-xs text-gray-500 mb-1">IOC</div>
                            <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= (int)$linkedIOC['id'] ?>" class="font-mono text-sm text-aegisz-accent hover:underline"><?= Security::e($linkedIOC['value']) ?></a>
                            <div class="text-xs text-gray-500 mt-1">Type: <?= Security::e($linkedIOC['type']) ?> · Confidence: <?= (int)$linkedIOC['confidence_score'] ?>%</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($linkedAsset): ?>
                        <div class="p-3 bg-gray-800/50 rounded-lg border-l-2 border-aegisz-success-600">
                            <div class="text-xs text-gray-500 mb-1">Asset</div>
                            <a href="<?= Security::e($baseUrl) ?>/assets/detail?id=<?= (int)$linkedAsset['id'] ?>" class="text-sm text-white hover:text-aegisz-accent font-medium"><?= Security::e($linkedAsset['name']) ?></a>
                            <div class="text-xs text-gray-500 mt-1">Criticality: <?= Security::e(ucfirst($linkedAsset['criticality'])) ?> · IP: <?= Security::e($linkedAsset['ip_address']??'—') ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($linkedAlert): ?>
                        <div class="p-3 bg-gray-800/50 rounded-lg border-l-2 border-aegisz-danger-600">
                            <div class="text-xs text-gray-500 mb-1">Alert</div>
                            <div class="text-sm text-white font-medium"><?= Security::e($linkedAlert['title']) ?></div>
                            <div class="text-xs text-gray-500 mt-1">Status: <?= Security::e(ucfirst($linkedAlert['status'])) ?> · Severity: <?= Security::e(ucfirst($linkedAlert['severity'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($linkedIncident): ?>
                        <div class="p-3 bg-gray-800/50 rounded-lg border-l-2 border-orange-600">
                            <div class="text-xs text-gray-500 mb-1">Incident</div>
                            <a href="<?= Security::e($baseUrl) ?>/incidents/detail?id=<?= (int)$linkedIncident['id'] ?>" class="text-sm text-white hover:text-aegisz-accent font-medium"><?= Security::e($linkedIncident['title']) ?></a>
                            <div class="text-xs text-gray-500 mt-1">Status: <?= Security::e(ucfirst($linkedIncident['status'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!$linkedIOC && !$linkedAsset && !$linkedAlert && !$linkedIncident): ?>
                        <p class="text-gray-600 text-sm">No linked records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">Details</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="text-gray-200 text-xs font-mono"><?= Security::e($typeLabel) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Severity</span><span class="text-<?= $sevColor ?>-400"><?= Security::e(ucfirst($corr['severity'])) ?></span></div>
                    <div>
                        <div class="flex justify-between mb-1"><span class="text-gray-500">Confidence</span><span class="text-gray-200"><?= (int)$corr['confidence'] ?>%</span></div>
                        <div class="w-full bg-gray-700 rounded-full h-2"><div class="h-2 rounded-full bg-aegisz-accent" style="width:<?= (int)$corr['confidence'] ?>%"></div></div>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">Source Feed</span><span class="text-gray-200 text-xs"><?= Security::e($corr['source_feed']??'—') ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Created</span><span class="text-gray-400 text-xs"><?= Security::e($corr['created_at']) ?></span></div>
                </div>
            </div>

            <!-- Mini timeline -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">Timeline</h2>
                <?php
                $timeline = [
                    ['timestamp' => $corr['created_at'], 'event' => 'Correlation Detected', 'detail' => $typeLabel, 'color' => 'accent'],
                ];
                $view->partial('timeline', ['timeline' => $timeline]);
                ?>
            </div>
        </div>
    </div>
</div>
