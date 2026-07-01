<?php
/** AegisZ Sentinel - Create User Form (v0.5.0) */
use App\Core\Security;
$old = $old ?? [];
?>
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="<?= Security::e($baseUrl) ?>/admin/users" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Users
        </a>
        <h1 class="text-2xl font-bold text-white">Create User</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="mb-5 p-4 bg-red-900/30 border border-red-700 rounded-lg">
            <div class="text-red-400 font-medium text-sm mb-2">Please fix the following errors:</div>
            <ul class="text-red-300 text-sm space-y-1 list-disc list-inside">
                <?php foreach ($errors as $e): ?>
                    <li><?= Security::e($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
        <form method="POST" action="<?= Security::e($baseUrl) ?>/admin/users/store">
            <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">

            <div class="grid grid-cols-1 gap-5">

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Username <span class="text-red-400">*</span></label>
                    <input type="text" name="username" required value="<?= Security::e($old['username'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                  focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent"
                           placeholder="e.g. john_analyst">
                    <p class="text-xs text-gray-600 mt-1">3–50 chars, letters/numbers/underscores only</p>
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Full Name</label>
                    <input type="text" name="full_name" value="<?= Security::e($old['full_name'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                  focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent"
                           placeholder="Optional display name">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" name="email" required value="<?= Security::e($old['email'] ?? '') ?>"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                  focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent"
                           placeholder="user@example.com">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Role <span class="text-red-400">*</span></label>
                    <select name="role" required
                            class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                   focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent">
                        <?php foreach (['viewer' => 'Viewer', 'analyst' => 'Analyst', 'admin' => 'Admin'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($old['role'] ?? 'viewer') === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="mt-2 space-y-1 text-xs text-gray-600">
                        <div><span class="text-gray-400">Viewer</span> — read-only dashboard and intelligence access</div>
                        <div><span class="text-gray-400">Analyst</span> — full alert and incident workflow access</div>
                        <div><span class="text-gray-400">Admin</span> — all access including user management</div>
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Status</label>
                    <select name="status"
                            class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                   focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent">
                        <option value="active" <?= ($old['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($old['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Password <span class="text-red-400">*</span></label>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                  focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent"
                           placeholder="Min 10 chars, upper, lower, digit, special">
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Confirm Password <span class="text-red-400">*</span></label>
                    <input type="password" name="password_confirm" required autocomplete="new-password"
                           class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm
                                  focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent"
                           placeholder="Re-enter password">
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                        class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition-colors">
                    Create User
                </button>
                <a href="<?= Security::e($baseUrl) ?>/admin/users" class="text-sm text-gray-400 hover:text-white">Cancel</a>
            </div>
        </form>
    </div>
</div>
