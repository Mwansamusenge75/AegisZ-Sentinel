<?php use App\Core\Security; use App\Middleware\RoleMiddleware; ?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Assets
            </h1>
            <p class="text-gray-400 text-sm mt-1"><?= number_format($paginator->total) ?> asset(s) in inventory</p>
        </div>
        <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
            <a href="<?= Security::e($baseUrl) ?>/assets/create"
               class="inline-flex items-center gap-2 bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Asset
            </a>
        <?php endif; ?>
    </div>
    <?php if (!empty($flashSuccess)): ?><div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div><?php endif; ?>
    <?php if (!empty($flashError)): ?><div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div><?php endif; ?>

    <!-- Search & Filters -->
    <form method="GET" action="<?= Security::e($baseUrl) ?>/assets" class="mb-4 flex flex-wrap gap-2">
        <input type="text" name="q" value="<?= Security::e($q->search) ?>" placeholder="Search name, IP, hostname…"
               class="flex-1 min-w-48 bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
        <select name="criticality" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Criticalities</option>
            <?php foreach (['low','medium','high','critical'] as $v): ?><option value="<?= $v ?>" <?= $q->filter('criticality') === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
        </select>
        <select name="asset_type" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Types</option>
            <?php foreach (['server','endpoint','router','app','db','website'] as $v): ?><option value="<?= $v ?>" <?= $q->filter('asset_type') === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
        </select>
        <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Statuses</option>
            <?php foreach (['active','inactive','maintenance'] as $v): ?><option value="<?= $v ?>" <?= $q->filter('status') === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option><?php endforeach; ?>
        </select>
        <?php if (!empty($departments)): ?>
        <select name="department" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Departments</option>
            <?php foreach ($departments as $d): ?><option value="<?= Security::e($d) ?>" <?= $q->filter('department') === $d ? 'selected' : '' ?>><?= Security::e($d) ?></option><?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">Search</button>
        <a href="<?= Security::e($baseUrl) ?>/assets" class="text-sm text-gray-500 hover:text-white self-center">Clear</a>
    </form>

    <div class="bg-aegisz-panel border border-gray-700 rounded-lg overflow-hidden">
        <?php if (empty($assets)): ?>
            <div class="p-12 text-center text-gray-500"><svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>No assets found.</div>
        <?php else: ?>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-700 bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-3"><a href="?<?= Security::e($q->toQueryString(['sort'=>'name','dir'=>$q->direction==='ASC'?'desc':'asc'])) ?>" class="hover:text-white">Name</a></th>
                        <th class="px-4 py-3">IP / Host</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Criticality</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">OS</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    <?php foreach ($assets as $asset): ?>
                        <?php
                            $critColor   = ['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$asset->criticality] ?? 'gray';
                            $statusColor = ['active'=>'aegisz-success','inactive'=>'gray','maintenance'=>'aegisz-warning'][$asset->status] ?? 'gray';
                        ?>
                        <tr class="text-gray-300 hover:bg-gray-700/20">
                            <td class="px-4 py-3"><a href="<?= Security::e($baseUrl) ?>/assets/detail?id=<?= $asset->id ?>" class="text-white font-medium hover:text-aegisz-accent"><?= Security::e($asset->name) ?></a></td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-400"><?= Security::e($asset->ipAddress ?? $asset->hostname ?? '—') ?></td>
                            <td class="px-4 py-3 text-gray-400"><?= Security::e(ucfirst($asset->assetType)) ?></td>
                            <td class="px-4 py-3 text-gray-400"><?= Security::e($asset->department ?? '—') ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs bg-<?= $critColor ?>-900/30 text-<?= $critColor ?>-400"><?= Security::e(ucfirst($asset->criticality)) ?></span></td>
                            <td class="px-4 py-3"><span class="flex items-center gap-1 text-xs text-<?= $statusColor ?>-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span><?= Security::e(ucfirst($asset->status)) ?></span></td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?= Security::e($asset->operatingSystem ?? '—') ?></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <a href="<?= Security::e($baseUrl) ?>/assets/detail?id=<?= $asset->id ?>" class="text-xs text-aegisz-accent hover:underline">View</a>
                                    <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
                                        <a href="<?= Security::e($baseUrl) ?>/assets/edit?id=<?= $asset->id ?>" class="text-xs text-gray-400 hover:underline">Edit</a>
                                    <?php endif; ?>
                                </div>
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
