<?php use App\Core\Security; ?>
<div class="max-w-6xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/threats" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Threats</a>
    <?php $sevColor = ['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$threat->severity]??'gray'; ?>
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-2 flex-wrap">
            <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400"><?= Security::e(ucfirst($threat->severity)) ?></span>
            <span class="px-2 py-0.5 bg-gray-700 rounded text-xs uppercase"><?= Security::e($threat->sourceFeed ?? 'unknown') ?></span>
            <?php if ($threat->cveId): ?><span class="font-mono text-xs text-aegisz-accent bg-aegisz-accent/10 px-2 py-0.5 rounded"><?= Security::e($threat->cveId) ?></span><?php endif; ?>
            <?php if ($threat->mitreTechnique): ?><span class="font-mono text-xs text-gray-300 bg-gray-700 px-2 py-0.5 rounded"><?= Security::e($threat->mitreTechnique) ?></span><?php endif; ?>
        </div>
        <h1 class="text-2xl font-bold text-white"><?= Security::e($threat->title) ?></h1>
        <p class="text-gray-500 text-sm mt-1">Ingested: <?= Security::e($threat->createdAt) ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <!-- Description -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3">Description</h2>
                <?php if ($threat->description): ?>
                    <p class="text-gray-300 text-sm leading-relaxed"><?= nl2br(Security::e($threat->description)) ?></p>
                <?php else: ?>
                    <p class="text-gray-600 text-sm">No description available from feed.</p>
                <?php endif; ?>
                <?php if ($threat->affectedSystems): ?>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <div class="text-xs text-gray-500 mb-1">Affected Systems</div>
                        <p class="text-sm text-gray-300"><?= Security::e($threat->affectedSystems) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- MITRE Info -->
            <?php if ($mitreInfo): ?>
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    MITRE ATT&amp;CK Mapping
                </h2>
                <div class="flex items-center gap-4 p-3 bg-gray-800/60 rounded-lg">
                    <div class="text-2xl font-bold text-aegisz-accent font-mono"><?= Security::e($mitreInfo['id']) ?></div>
                    <div>
                        <div class="text-white font-medium"><?= Security::e($mitreInfo['name']) ?></div>
                        <div class="text-xs text-gray-500">Tactic: <?= Security::e($mitreInfo['tactic']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Linked IOCs -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3 flex items-center justify-between">
                    Related IOCs <span class="text-xs text-gray-500 font-normal"><?= count($linkedIOCs) ?></span>
                </h2>
                <?php if (empty($linkedIOCs)): ?>
                    <p class="text-gray-600 text-sm">No IOCs linked from this feed source.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($linkedIOCs as $ioc): ?>
                            <div class="flex items-center justify-between p-2 bg-gray-800/40 rounded">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs bg-gray-700 px-1.5 py-0.5 rounded uppercase shrink-0"><?= Security::e($ioc['type']) ?></span>
                                    <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= (int)$ioc['id'] ?>" class="font-mono text-xs text-aegisz-accent truncate hover:underline"><?= Security::e(substr($ioc['value']??'',0,50)) ?></a>
                                </div>
                                <span class="text-xs text-gray-500 shrink-0"><?= (int)$ioc['confidence_score'] ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Correlations -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3 flex items-center justify-between">
                    Correlations <span class="text-xs text-gray-500 font-normal"><?= count($correlations) ?></span>
                </h2>
                <?php if (empty($correlations)): ?>
                    <p class="text-gray-600 text-sm">No correlations linked to this threat's feed source.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($correlations as $corr): ?>
                            <?php $sevColor2 = ['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$corr['severity']]??'gray'; ?>
                            <div class="p-2 bg-gray-800/40 rounded border-l-2 border-aegisz-accent-600">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-mono text-aegisz-accent"><?= Security::e(str_replace('_',' → ',strtoupper($corr['correlation_type']??''))) ?></span>
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-<?= $sevColor2 ?>-900/30 text-<?= $sevColor2 ?>-400"><?= Security::e(ucfirst($corr['severity'])) ?></span>
                                </div>
                                <p class="text-xs text-gray-400 truncate"><?= Security::e(substr($corr['explanation']??'',0,100)) ?></p>
                                <a href="<?= Security::e($baseUrl) ?>/correlations/detail?id=<?= (int)$corr['id'] ?>" class="text-xs text-aegisz-accent hover:underline mt-1 block">View correlation →</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right sidebar: metadata -->
        <div class="space-y-4">
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">Details</h2>
                <div class="space-y-2 text-sm">
                    <?php foreach ([
                        'ID'        => '#'.$threat->id,
                        'Severity'  => ucfirst($threat->severity),
                        'Source'    => strtoupper($threat->sourceFeed??'—'),
                        'CVE'       => $threat->cveId??'—',
                        'MITRE'     => $threat->mitreTechnique??'—',
                        'Ingested'  => $threat->createdAt??'—',
                    ] as $l=>$v): ?>
                        <div class="flex justify-between gap-2">
                            <span class="text-gray-500 shrink-0"><?= $l ?></span>
                            <span class="text-gray-200 text-right text-xs font-mono"><?= Security::e($v) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
