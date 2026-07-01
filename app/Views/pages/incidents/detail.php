<?php
/** AegisZ Sentinel - Incident Detail View (v0.5.0) */
use App\Core\Security;
use App\Middleware\RoleMiddleware;
$canAct = RoleMiddleware::currentUserHasRole('analyst');
?>
<div class="max-w-5xl mx-auto">

    <!-- Back + Header -->
    <div class="mb-6">
        <a href="<?= Security::e($baseUrl) ?>/incidents"
           class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Incidents
        </a>
        <?php
            $sevColor = ['low' => 'gray', 'medium' => 'aegisz-warning', 'high' => 'orange', 'critical' => 'aegisz-danger'][$incident['severity']] ?? 'gray';
            $statusColor = [
                'open'          => 'aegisz-danger',
                'investigating' => 'aegisz-warning',
                'contained'     => 'aegisz-accent',
                'resolved'      => 'aegisz-success',
                'closed'        => 'gray',
            ][$incident['status']] ?? 'gray';
        ?>
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    <span class="text-xs font-mono text-gray-500">#<?= (int) $incident['id'] ?></span>
                    <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400">
                        <?= Security::e(ucfirst($incident['severity'])) ?>
                    </span>
                    <span class="px-2 py-0.5 rounded text-xs bg-<?= $statusColor ?>-900/30 text-<?= $statusColor ?>-400 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        <?= Security::e(ucfirst($incident['status'])) ?>
                    </span>
                    <?php if ($incident['assigned_username']): ?>
                        <span class="text-xs text-gray-400">Assigned to: <span class="text-white"><?= Security::e($incident['assigned_username']) ?></span></span>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-bold text-white"><?= Security::e($incident['title']) ?></h1>
                <p class="text-gray-500 text-sm mt-1">Created: <?= Security::e($incident['created_at']) ?></p>
            </div>

            <!-- Status transition button -->
            <?php if ($canAct && !empty($allowedNext)): ?>
                <button type="button"
                        onclick="document.getElementById('transition-modal').classList.remove('hidden')"
                        class="shrink-0 inline-flex items-center gap-2 bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Transition Status
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm">
            <?= Security::e($flashSuccess) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm">
            <?= Security::e($flashError) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Main: Notes timeline -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Add Note (analysts/admins only) -->
            <?php if ($canAct): ?>
                <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                    <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Add Analyst Note
                    </h2>
                    <form method="POST" action="<?= Security::e($baseUrl) ?>/incidents/note">
                        <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                        <input type="hidden" name="incident_id" value="<?= (int) $incident['id'] ?>">
                        <textarea name="note" rows="4" required maxlength="5000"
                                  class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-3 text-white text-sm
                                         focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent
                                         resize-none"
                                  placeholder="Add investigation notes, findings, or context..."></textarea>
                        <div class="mt-3">
                            <button type="submit"
                                    class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                                Add Note
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Notes Timeline -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    Analyst Notes
                    <span class="text-xs text-gray-500 font-normal">(<?= count($notes) ?>)</span>
                </h2>

                <?php if (empty($notes)): ?>
                    <p class="text-gray-500 text-sm text-center py-6">No notes yet. Add the first note above.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($notes as $note): ?>
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-300 shrink-0 mt-0.5">
                                    <?= strtoupper(substr($note['username'] ?? '?', 0, 1)) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-sm font-medium text-white"><?= Security::e($note['username'] ?? 'Unknown') ?></span>
                                        <span class="text-xs text-gray-500"><?= Security::e($note['created_at']) ?></span>
                                    </div>
                                    <div class="bg-gray-800/60 rounded-lg p-3 text-sm text-gray-300 leading-relaxed border border-gray-700/50">
                                        <?= nl2br(Security::e($note['note'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar: Workflow Log + Metadata -->
        <div class="space-y-4">

            <!-- Incident Metadata -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">Details</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Severity</span>
                        <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400">
                            <?= Security::e(ucfirst($incident['severity'])) ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="text-white"><?= Security::e(ucfirst($incident['status'])) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Assigned To</span>
                        <span class="text-white"><?= Security::e($incident['assigned_username'] ?? 'Unassigned') ?></span>
                    </div>
                    <?php if ($incident['linked_alert_id']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Linked Alert</span>
                            <span class="text-aegisz-accent">#<?= (int) $incident['linked_alert_id'] ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($incident['linked_asset_id']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Linked Asset</span>
                            <span class="text-aegisz-accent">#<?= (int) $incident['linked_asset_id'] ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Created</span>
                        <span class="text-gray-300 text-xs"><?= Security::e(substr($incident['created_at'], 0, 10)) ?></span>
                    </div>
                </div>
            </div>

            <!-- Workflow History -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Status History
                </h2>
                <?php if (empty($workflowLog)): ?>
                    <p class="text-gray-500 text-xs">No transitions recorded.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($workflowLog as $entry): ?>
                            <div class="text-xs border-l-2 border-gray-600 pl-3">
                                <div class="text-gray-300">
                                    <span class="text-gray-500"><?= Security::e($entry['from_status']) ?></span>
                                    <span class="text-gray-600 mx-1">→</span>
                                    <span class="text-white font-medium"><?= Security::e($entry['to_status']) ?></span>
                                </div>
                                <div class="text-gray-500 mt-0.5">
                                    <?= Security::e($entry['username'] ?? 'Unknown') ?> &middot; <?= Security::e($entry['created_at']) ?>
                                </div>
                                <?php if ($entry['note']): ?>
                                    <div class="text-gray-400 mt-1 italic">"<?= Security::e(substr($entry['note'], 0, 80)) ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Transition Modal -->
<?php if ($canAct && !empty($allowedNext)): ?>
    <div id="transition-modal"
         class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-aegisz-panel border border-gray-700 rounded-xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="text-white font-semibold mb-1">Transition Incident #<?= (int) $incident['id'] ?></h3>
            <p class="text-gray-400 text-sm mb-5"><?= Security::e($incident['title']) ?></p>
            <form method="POST" action="<?= Security::e($baseUrl) ?>/incidents/transition">
                <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                <input type="hidden" name="incident_id" value="<?= (int) $incident['id'] ?>">
                <div class="mb-4">
                    <label class="block text-sm text-gray-400 mb-1.5">New Status</label>
                    <select name="new_status" required
                            class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm
                                   focus:outline-none focus:border-aegisz-accent">
                        <?php foreach ($allowedNext as $ns): ?>
                            <option value="<?= Security::e($ns) ?>"><?= Security::e(ucfirst($ns)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-5">
                    <label class="block text-sm text-gray-400 mb-1.5">Note (optional)</label>
                    <textarea name="note" rows="3"
                              class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm
                                     focus:outline-none focus:border-aegisz-accent resize-none"
                              placeholder="Optional transition note..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit"
                            class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                        Confirm Transition
                    </button>
                    <button type="button"
                            onclick="document.getElementById('transition-modal').classList.add('hidden')"
                            class="text-sm text-gray-400 hover:text-white px-4 py-2">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
