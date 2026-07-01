<?php
/** AegisZ Sentinel - User Management List (v0.5.0) */
use App\Core\Security;
?>
<div class="max-w-6xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                User Management
            </h1>
            <p class="text-gray-400 text-sm mt-1">Platform users and role assignments</p>
        </div>
        <a href="<?= Security::e($baseUrl) ?>/admin/users/create"
           class="inline-flex items-center gap-2 bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create User
        </a>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($flashSuccess)): ?>
        <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div>
    <?php endif; ?>

    <div class="bg-aegisz-panel border border-gray-700 rounded-lg overflow-hidden">
        <?php if (empty($users)): ?>
            <div class="p-8 text-center text-gray-500">No users found.</div>
        <?php else: ?>
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-700 bg-gray-800/50">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Last Login</th>
                        <th class="px-5 py-3">Created</th>
                        <th class="px-5 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/50">
                    <?php foreach ($users as $user): ?>
                        <?php
                            $roleColor  = ['admin' => 'aegisz-danger', 'analyst' => 'aegisz-accent', 'viewer' => 'gray'][$user->role] ?? 'gray';
                            $statusColor = $user->status === 'active' ? 'aegisz-success' : ($user->status === 'locked' ? 'aegisz-danger' : 'gray');
                            $isSelf = $user->id === (int)($currentUser['id'] ?? 0);
                        ?>
                        <tr class="text-gray-300 hover:bg-gray-700/20">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-300">
                                        <?= strtoupper(substr($user->username, 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="text-white font-medium"><?= Security::e($user->username) ?><?= $isSelf ? ' <span class="text-xs text-gray-500">(you)</span>' : '' ?></div>
                                        <?php if ($user->fullName): ?><div class="text-xs text-gray-500"><?= Security::e($user->fullName) ?></div><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-400"><?= Security::e($user->email) ?></td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded text-xs bg-<?= $roleColor ?>-900/30 text-<?= $roleColor ?>-400 font-medium">
                                    <?= Security::e(ucfirst($user->role)) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="flex items-center gap-1.5 text-<?= $statusColor ?>-400 text-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <?= Security::e(ucfirst($user->status)) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs"><?= Security::e($user->lastLoginAt ?? 'Never') ?></td>
                            <td class="px-5 py-3 text-gray-500 text-xs"><?= Security::e(substr($user->createdAt ?? '', 0, 10)) ?></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="<?= Security::e($baseUrl) ?>/admin/users/edit?id=<?= $user->id ?>"
                                       class="text-xs text-aegisz-accent hover:underline">Edit</a>
                                    <?php if (!$isSelf): ?>
                                        <form method="POST" action="<?= Security::e($baseUrl) ?>/admin/users/delete"
                                              onsubmit="return confirm('Delete user <?= Security::e($user->username) ?>? This cannot be undone.')">
                                            <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken ?? '') ?>">
                                            <input type="hidden" name="user_id" value="<?= $user->id ?>">
                                            <button type="submit" class="text-xs text-red-400 hover:underline">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
