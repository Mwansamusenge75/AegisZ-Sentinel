<?php use App\Core\Security; use App\Middleware\RoleMiddleware; ?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Indicators of Compromise
            </h1>
            <p class="text-gray-400 text-sm mt-1"><?= number_format($paginator->total) ?> IOC(s) in database</p>
        </div>
        <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
            <a href="<?= Security::e($baseUrl) ?>/iocs/create"
               class="inline-flex items-center gap-2 bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add IOC
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($flashSuccess)): ?><div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div><?php endif; ?>
    <?php if (!empty($flashError)): ?><div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div><?php endif; ?>

    <!-- Filters -->
    <form method="GET" action="<?= Security::e($baseUrl) ?>/iocs" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="q" value="<?= Security::e($q->search) ?>" placeholder="Search value or source…"
               class="flex-1 min-w-48 bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
        <select name="type" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Types</option>
            <?php foreach (['ip','domain','url','hash'] as $t): ?><option value="<?= $t ?>" <?= $q->filter('type')===$t?'selected':'' ?>><?= strtoupper($t) ?></option><?php endforeach; ?>
        </select>
        <?php if (!empty($sources)): ?>
        <select name="source" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Sources</option>
            <?php foreach ($sources as $s): ?><option value="<?= Security::e($s) ?>" <?= $q->filter('source')===$s?'selected':'' ?>><?= Security::e(ucfirst($s)) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="false_positive" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All IOCs</option>
            <option value="0" <?= $q->filter('false_positive')==='0'?'selected':'' ?>>Active</option>
            <option value="1" <?= $q->filter('false_positive')==='1'?'selected':'' ?>>False Positives</option>
        </select>
        <select name="confidence_min" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">Any Confidence</option>
            <?php foreach ([50=>'>50%',70=>'>70%',80=>'>80%',90=>'>90%'] as $v=>$l): ?><option value="<?= $v ?>" <?= $q->filter('confidence_min')===(string)$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">Filter</button>
        <a href="<?= Security::e($baseUrl) ?>/iocs" class="text-sm text-gray-500 hover:text-white self-center">Clear</a>
    </form>

    <div class="bg-aegisz-panel border border-gray-700 rounded-lg overflow-hidden">
        <?php if (empty($iocs)): ?>
            <div class="p-12 text-center text-gray-500"><svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>No IOCs found.</div>
        <?php else: ?>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-700 bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-3">Value</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Confidence</th>
                        <th class="px-4 py-3">First Seen</th>
                        <th class="px-4 py-3">Last Seen</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    <?php foreach ($iocs as $ioc): ?>
                        <?php
                            $conf = (int) $ioc->confidenceScore;
                            $confColor = $conf >= 80 ? 'aegisz-danger' : ($conf >= 60 ? 'aegisz-warning' : 'gray');
                        ?>
                        <tr class="text-gray-300 hover:bg-gray-700/20 <?= $ioc->falsePositive ? 'opacity-50' : '' ?>">
                            <td class="px-4 py-3">
                                <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= $ioc->id ?>" class="font-mono text-xs text-aegisz-accent hover:underline max-w-xs truncate block" title="<?= Security::e($ioc->value) ?>">
                                    <?= Security::e(strlen($ioc->value) > 45 ? substr($ioc->value, 0, 45).'…' : $ioc->value) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-700 rounded text-xs uppercase"><?= Security::e($ioc->type) ?></span></td>
                            <td class="px-4 py-3 text-gray-400 text-xs"><?= Security::e($ioc->source ?? '—') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-700 rounded-full h-1.5"><div class="h-1.5 rounded-full bg-<?= $confColor ?>-500" style="width:<?= $conf ?>%"></div></div>
                                    <span class="text-xs text-gray-400"><?= $conf ?>%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?= Security::e(substr($ioc->firstSeen ?? '—', 0, 10)) ?></td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?= Security::e(substr($ioc->lastSeen ?? '—', 0, 10)) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($ioc->falsePositive): ?>
                                    <span class="px-2 py-0.5 rounded text-xs bg-gray-700 text-gray-400">False Positive</span>
                                <?php elseif ($ioc->isExpired()): ?>
                                    <span class="px-2 py-0.5 rounded text-xs bg-yellow-900/30 text-yellow-400">Expired</span>
                                <?php else: ?>
                                    <span class="flex items-center gap-1 text-xs text-aegisz-success-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>Active</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= $ioc->id ?>" class="text-xs text-aegisz-accent hover:underline">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="px-4 pb-4">
                <?php $view->partial('pagination', ['paginator' => $paginator, 'q' => $q]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
