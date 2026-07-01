<?php use App\Core\Security; ?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white flex items-center gap-3">
            <svg class="w-7 h-7 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Threat Intelligence Explorer
        </h1>
        <p class="text-gray-400 text-sm mt-1"><?= number_format($paginator->total) ?> threat(s) in database · Read-only — threats are ingested by workers</p>
    </div>

    <!-- Search & Filters -->
    <form method="GET" action="<?= Security::e($baseUrl) ?>/threats" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="q" value="<?= Security::e($q->search) ?>" placeholder="Search title, description, CVE…"
               class="flex-1 min-w-64 bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
        <select name="severity" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Severities</option>
            <?php foreach (['low','medium','high','critical'] as $v): ?><option value="<?= $v ?>" <?= $q->filter('severity')===$v?'selected':'' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
        </select>
        <?php if (!empty($sources)): ?>
        <select name="source_feed" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Sources</option>
            <?php foreach ($sources as $s): ?><option value="<?= Security::e($s) ?>" <?= $q->filter('source_feed')===$s?'selected':'' ?>><?= Security::e(strtoupper($s)) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if (!empty($techniques)): ?>
        <select name="mitre_technique" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All MITRE</option>
            <?php foreach ($techniques as $t):
                $name = '';
                foreach ($registry as $r) { if ($r['id']===$t) { $name = $r['name']; break; } }
            ?>
                <option value="<?= Security::e($t) ?>" <?= $q->filter('mitre_technique')===$t?'selected':'' ?>><?= Security::e($t) ?><?= $name ? ' — '.$name : '' ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input type="date" name="date_from" value="<?= Security::e($q->filter('date_from')) ?>" title="From date"
               class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
        <input type="date" name="date_to" value="<?= Security::e($q->filter('date_to')) ?>" title="To date"
               class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
        <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">Search</button>
        <a href="<?= Security::e($baseUrl) ?>/threats" class="text-sm text-gray-500 hover:text-white self-center">Clear</a>
    </form>

    <div class="bg-aegisz-panel border border-gray-700 rounded-lg overflow-hidden">
        <?php if (empty($threats)): ?>
            <div class="p-12 text-center text-gray-500"><svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>No threats match your filters.</div>
        <?php else: ?>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-700 bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Severity</th>
                        <th class="px-4 py-3">CVE</th>
                        <th class="px-4 py-3">MITRE</th>
                        <th class="px-4 py-3">Ingested</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    <?php foreach ($threats as $threat): ?>
                        <?php $sevColor = ['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$threat->severity]??'gray'; ?>
                        <tr class="text-gray-300 hover:bg-gray-700/20">
                            <td class="px-4 py-3">
                                <a href="<?= Security::e($baseUrl) ?>/threats/detail?id=<?= $threat->id ?>" class="text-white font-medium hover:text-aegisz-accent line-clamp-1">
                                    <?= Security::e(strlen($threat->title)>70?substr($threat->title,0,70).'…':$threat->title) ?>
                                </a>
                            </td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-700 rounded text-xs uppercase"><?= Security::e($threat->sourceFeed ?? '—') ?></span></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400"><?= Security::e(ucfirst($threat->severity)) ?></span></td>
                            <td class="px-4 py-3 font-mono text-xs text-aegisz-accent"><?= Security::e($threat->cveId ?? '—') ?></td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-400"><?= Security::e($threat->mitreTechnique ?? '—') ?></td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?= Security::e(substr($threat->createdAt??'',0,10)) ?></td>
                            <td class="px-4 py-3"><a href="<?= Security::e($baseUrl) ?>/threats/detail?id=<?= $threat->id ?>" class="text-xs text-aegisz-accent hover:underline">View</a></td>
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
