<?php
use App\Core\Security;
use App\Core\Config;
$baseUrl = $baseUrl ?? Config::get('app.base_url', '/aegisz-sentinel');
$currentUser = $currentUser ?? \App\Core\Session::getUser();
?>
<nav class="bg-aegisz-panel border-b border-gray-700 px-6 py-3 flex items-center justify-between shrink-0">
    <!-- Brand -->
    <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-aegisz-accent/20 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <div>
            <span class="text-white font-bold text-sm"><?= Security::e($appName ?? 'AegisZ Sentinel') ?></span>
            <span class="text-gray-600 text-xs ml-2">v0.5.0</span>
        </div>
    </div>

    <!-- Right side: user info + logout -->
    <?php if ($currentUser): ?>
        <div class="flex items-center gap-4">
            <!-- Current user badge -->
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-300">
                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                </div>
                <div class="hidden sm:block">
                    <div class="text-sm text-white leading-none"><?= Security::e($currentUser['username']) ?></div>
                    <div class="text-xs text-gray-500 leading-none mt-0.5"><?= Security::e(ucfirst($currentUser['role'])) ?></div>
                </div>
            </div>
            <!-- Logout -->
            <form method="POST" action="<?= Security::e($baseUrl) ?>/logout">
                <input type="hidden" name="_csrf" value="<?= Security::e(\App\Core\Security::generateCsrfToken()) ?>">
                <button type="submit"
                        class="flex items-center gap-1.5 text-xs text-gray-400 hover:text-white transition-colors"
                        title="Sign out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="hidden sm:inline">Sign Out</span>
                </button>
            </form>
        </div>
    <?php endif; ?>
</nav>
