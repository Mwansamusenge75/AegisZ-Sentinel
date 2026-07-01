<?php use App\Core\Security; use App\Middleware\RoleMiddleware; ?>
<div class="max-w-6xl mx-auto">
    <a href="<?= Security::e($baseUrl) ?>/iocs" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to IOCs
    </a>

    <?php
        $conf      = (int) $ioc->confidenceScore;
        $confColor = $conf >= 80 ? 'aegisz-danger' : ($conf >= 60 ? 'aegisz-warning' : 'gray');
    ?>

    <div class="flex items-start justify-between gap-4 flex-wrap mb-6">
        <div>
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="px-2 py-0.5 bg-gray-700 rounded text-xs uppercase font-mono"><?= Security::e($ioc->type) ?></span>
                <?php if ($ioc->falsePositive): ?>
                    <span class="px-2 py-0.5 rounded text-xs bg-gray-700 text-gray-400">False Positive</span>
                <?php elseif ($ioc->isExpired()): ?>
                    <span class="px-2 py-0.5 rounded text-xs bg-yellow-900/30 text-yellow-400">Expired</span>
                <?php else: ?>
                    <span class="flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-green-900/30 text-aegisz-success-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>Active</span>
                <?php endif; ?>
            </div>
            <h1 class="text-xl font-bold text-white font-mono break-all"><?= Security::e($ioc->value) ?></h1>
            <p class="text-gray-500 text-sm mt-1">First seen: <?= Security::e($ioc->firstSeen ?? '—') ?> · Last seen: <?= Security::e($ioc->lastSeen ?? '—') ?></p>
        </div>
        <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
            <div class="flex gap-2 shrink-0 flex-wrap">
                <a href="<?= Security::e($baseUrl) ?>/iocs/edit?id=<?= $ioc->id ?>"
                   class="bg-gray-700 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">Edit</a>
                <form method="POST" action="<?= Security::e($baseUrl) ?>/iocs/flag">
                    <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                    <input type="hidden" name="ioc_id" value="<?= $ioc->id ?>">
                    <button type="submit" class="<?= $ioc->falsePositive ? 'bg-aegisz-success-700 hover:bg-green-600' : 'bg-yellow-800 hover:bg-yellow-700' ?> text-white text-sm px-4 py-2 rounded-lg transition-colors">
                        <?= $ioc->falsePositive ? 'Clear False Positive' : 'Flag False Positive' ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($flashSuccess)): ?><div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div><?php endif; ?>
    <?php if (!empty($flashError)): ?><div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Left: metadata -->
        <div class="space-y-4">
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">IOC Details</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Type</span><span class="text-white uppercase font-mono text-xs"><?= Security::e($ioc->type) ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Source</span><span class="text-gray-200"><?= Security::e($ioc->source ?? '—') ?></span></div>
                    <div>
                        <div class="flex justify-between mb-1"><span class="text-gray-500">Confidence</span><span class="text-<?= $confColor ?>-400 font-mono"><?= $conf ?>%</span></div>
                        <div class="w-full bg-gray-700 rounded-full h-2"><div class="h-2 rounded-full bg-<?= $confColor ?>-500" style="width:<?= $conf ?>%"></div></div>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-500">First Seen</span><span class="text-gray-400 text-xs"><?= Security::e($ioc->firstSeen ?? '—') ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Last Seen</span><span class="text-gray-400 text-xs"><?= Security::e($ioc->lastSeen ?? '—') ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Expires</span><span class="text-gray-400 text-xs"><?= Security::e($ioc->expiryAt ?? 'Never') ?></span></div>
                    <div class="flex justify-between"><span class="text-gray-500">False Positive</span><span class="<?= $ioc->falsePositive ? 'text-yellow-400' : 'text-gray-500' ?> text-xs"><?= $ioc->falsePositive ? 'Yes' : 'No' ?></span></div>
                    <?php if (!empty($ioc->getTagsArray())): ?>
                    <div>
                        <div class="text-gray-500 mb-1">Tags</div>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach ($ioc->getTagsArray() as $tag): ?>
                                <span class="px-2 py-0.5 bg-aegisz-accent/10 text-aegisz-accent text-xs rounded"><?= Security::e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- History Timeline -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">History</h2>
                <?php if (empty($history)): ?>
                    <p class="text-gray-600 text-sm">No history recorded.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($history as $h): ?>
                            <div class="border-l-2 border-aegisz-accent-600 pl-3">
                                <div class="text-sm text-gray-200 font-medium"><?= Security::e(ucwords(str_replace('_',' ',$h['event']))) ?></div>
                                <?php if ($h['detail']): ?><div class="text-xs text-gray-400 mt-0.5"><?= Security::e($h['detail']) ?></div><?php endif; ?>
                                <div class="text-xs text-gray-600"><?= Security::e($h['created_at']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: linked records -->
        <div class="lg:col-span-2 space-y-4">
            <?php
            $sections = [
                ['title'=>'Linked Correlations','data'=>$correlations,'key'=>'explanation','link'=>'/correlations/detail','color'=>'aegisz-accent'],
                ['title'=>'Linked Alerts',      'data'=>$alerts,      'key'=>'title',       'link'=>false,               'color'=>'aegisz-danger'],
                ['title'=>'Related Threats',    'data'=>$threats,     'key'=>'title',       'link'=>'/threats/detail',   'color'=>'orange'],
            ];
            foreach ($sections as $sec): ?>
                <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                    <h2 class="text-white font-semibold mb-3 flex items-center justify-between">
                        <?= $sec['title'] ?><span class="text-xs text-gray-500 font-normal"><?= count($sec['data']) ?></span>
                    </h2>
                    <?php if (empty($sec['data'])): ?>
                        <p class="text-gray-600 text-sm">None found.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($sec['data'] as $row): ?>
                                <div class="flex items-center gap-2 p-2 bg-gray-800/40 rounded border-l-2 border-<?= $sec['color'] ?>-600">
                                    <div class="flex-1 min-w-0 text-sm text-gray-200 truncate">
                                        <?php if ($sec['link'] && isset($row['id'])): ?>
                                            <a href="<?= Security::e($baseUrl.$sec['link']) ?>?id=<?= (int)$row['id'] ?>" class="hover:text-aegisz-accent">
                                                <?= Security::e(substr($row[$sec['key']] ?? '—', 0, 90)) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= Security::e(substr($row[$sec['key']] ?? '—', 0, 90)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($row['severity'])): ?>
                                        <span class="text-xs text-gray-500 shrink-0"><?= Security::e(ucfirst($row['severity'])) ?></span>
                                    <?php elseif (isset($row['confidence'])): ?>
                                        <span class="text-xs text-gray-500 shrink-0"><?= (int)$row['confidence'] ?>%</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
