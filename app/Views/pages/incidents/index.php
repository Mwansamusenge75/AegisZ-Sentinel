<?php
/** AegisZ Sentinel - Incident Queue (v0.5.0) */
use App\Core\Security;
use App\Middleware\RoleMiddleware;
$canAct = RoleMiddleware::currentUserHasRole('analyst');
?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Incidents
            </h1>
            <p class="text-gray-400 text-sm mt-1">
                Lifecycle: <span class="font-mono text-gray-300">open → investigating → contained → resolved → closed</span>
            </p>
        </div>
        <div class="text-sm text-gray-500"><?= count($incidents) ?> incident(s)</div>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" action="<?= Security::e($baseUrl) ?>/incidents" class="mb-4 flex gap-3">
        <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Statuses</option>
            <?php foreach (['open', 'investigating', 'contained', 'resolved', 'closed'] as $s): ?>
                <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="severity" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Severities</option>
            <?php foreach (['low', 'medium', 'high', 'critical'] as $sv): ?>
                <option value="<?= $sv ?>" <?= ($filters['severity'] ?? '') === $sv ? 'selected' : '' ?>><?= ucfirst($sv) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">Filter</button>
        <a href="<?= Security::e($baseUrl) ?>/incidents" class="text-sm text-gray-500 hover:text-white self-center ml-1">Clear</a>
    </form>

    <?php if (empty($incidents)): ?>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-12 text-center text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            No incidents found.
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($incidents as $inc): ?>
                <?php
                    $sevColor = ['low' => 'gray', 'medium' => 'aegisz-warning', 'high' => 'orange', 'critical' => 'aegisz-danger'][$inc['severity']] ?? 'gray';
                    $statusColor = [
                        'open'          => 'aegisz-danger',
                        'investigating' => 'aegisz-warning',
                        'contained'     => 'aegisz-accent',
                        'resolved'      => 'aegisz-success',
                        'closed'        => 'gray',
                    ][$inc['status']] ?? 'gray';
                ?>
                <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-xs font-mono text-gray-500">#<?= (int) $inc['id'] ?></span>
                                <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400"><?= Security::e(ucfirst($inc['severity'])) ?></span>
                                <span class="px-2 py-0.5 rounded text-xs bg-<?= $statusColor ?>-900/30 text-<?= $statusColor ?>-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <?= Security::e(ucfirst($inc['status'])) ?>
                                </span>
                                <?php if ($inc['assigned_username']): ?>
                                    <span class="text-xs text-gray-500">→ <?= Security::e($inc['assigned_username']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-white font-medium"><?= Security::e($inc['title']) ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?= Security::e($inc['created_at']) ?></div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="<?= Security::e($baseUrl) ?>/incidents/detail?id=<?= (int) $inc['id'] ?>"
                               class="text-xs bg-gray-700 hover:bg-gray-600 text-gray-200 px-3 py-1.5 rounded-lg transition-colors">
                               View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
