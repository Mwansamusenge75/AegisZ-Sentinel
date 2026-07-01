<?php
/** AegisZ Sentinel - Alert Queue (v0.5.0) */
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
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Alert Queue
            </h1>
            <p class="text-gray-400 text-sm mt-1">
                Lifecycle: <span class="font-mono text-gray-300">open → acknowledged → assigned → escalated → resolved → closed</span>
            </p>
        </div>
        <div class="text-sm text-gray-500"><?= count($alerts) ?> alert(s)</div>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <form method="GET" action="<?= Security::e($baseUrl) ?>/alerts" class="mb-4 flex gap-3">
        <select name="status" class="bg-gray-800 border border-gray-700 text-gray-300 text-sm rounded-lg px-3 py-2 focus:outline-none focus:border-aegisz-accent">
            <option value="">All Statuses</option>
            <?php foreach (['open', 'acknowledged', 'assigned', 'escalated', 'resolved', 'closed'] as $s): ?>
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
        <a href="<?= Security::e($baseUrl) ?>/alerts" class="text-sm text-gray-500 hover:text-white self-center ml-1">Clear</a>
    </form>

    <?php if (empty($alerts)): ?>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-12 text-center text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            No alerts found.
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($alerts as $alert): ?>
                <?php
                    $sevColor = ['low' => 'gray', 'medium' => 'aegisz-warning', 'high' => 'orange', 'critical' => 'aegisz-danger'][$alert['severity']] ?? 'gray';
                    $statusColor = [
                        'open'         => 'aegisz-danger',
                        'acknowledged' => 'aegisz-warning',
                        'assigned'     => 'aegisz-accent',
                        'escalated'    => 'orange',
                        'resolved'     => 'aegisz-success',
                        'closed'       => 'gray',
                    ][$alert['status']] ?? 'gray';

                    // Valid next statuses for this alert
                    $transitions = [
                        'open'         => ['acknowledged', 'closed'],
                        'acknowledged' => ['assigned', 'resolved', 'closed'],
                        'assigned'     => ['escalated', 'resolved', 'closed'],
                        'escalated'    => ['resolved', 'closed'],
                        'resolved'     => ['closed'],
                        'closed'       => [],
                    ];
                    $nextStatuses = $transitions[$alert['status']] ?? [];
                ?>
                <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-xs font-mono text-gray-500">#<?= (int) $alert['id'] ?></span>
                                <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400"><?= Security::e(ucfirst($alert['severity'])) ?></span>
                                <span class="px-2 py-0.5 rounded text-xs bg-<?= $statusColor ?>-900/30 text-<?= $statusColor ?>-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <?= Security::e(ucfirst($alert['status'])) ?>
                                </span>
                                <?php if ($alert['assigned_username']): ?>
                                    <span class="text-xs text-gray-500">→ <?= Security::e($alert['assigned_username']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-white font-medium"><?= Security::e($alert['title']) ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?= Security::e($alert['created_at']) ?></div>
                        </div>

                        <!-- Workflow actions (analyst/admin only) -->
                        <?php if ($canAct && !empty($nextStatuses)): ?>
                            <div class="shrink-0">
                                <button type="button"
                                        onclick="document.getElementById('modal-<?= $alert['id'] ?>').classList.remove('hidden')"
                                        class="text-xs bg-gray-700 hover:bg-gray-600 text-gray-200 px-3 py-1.5 rounded-lg transition-colors">
                                    Transition
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Transition Modal -->
                <?php if ($canAct && !empty($nextStatuses)): ?>
                    <div id="modal-<?= $alert['id'] ?>" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
                        <div class="bg-aegisz-panel border border-gray-700 rounded-xl p-6 w-full max-w-md shadow-2xl">
                            <h3 class="text-white font-semibold mb-1">Transition Alert #<?= (int) $alert['id'] ?></h3>
                            <p class="text-gray-400 text-sm mb-4"><?= Security::e($alert['title']) ?></p>
                            <form method="POST" action="<?= Security::e($baseUrl) ?>/alerts/transition">
                                <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                                <input type="hidden" name="alert_id" value="<?= (int) $alert['id'] ?>">
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-400 mb-1.5">New Status</label>
                                    <select name="new_status" required class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent">
                                        <?php foreach ($nextStatuses as $ns): ?>
                                            <option value="<?= $ns ?>"><?= ucfirst($ns) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-400 mb-1.5">Note (optional)</label>
                                    <textarea name="note" rows="3" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent" placeholder="Optional transition note..."></textarea>
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                                        Confirm
                                    </button>
                                    <button type="button" onclick="document.getElementById('modal-<?= $alert['id'] ?>').classList.add('hidden')"
                                            class="text-sm text-gray-400 hover:text-white px-4 py-2">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
