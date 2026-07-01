<?php
use App\Core\Security;
use App\Core\Config;
use App\Middleware\RoleMiddleware;
$baseUrl     = $baseUrl ?? Config::get('app.base_url', '/aegisz-sentinel');
$currentUri  = $_SERVER['REQUEST_URI'] ?? '';
$isActive    = fn(string $path) => (rtrim($currentUri, '/') === rtrim($baseUrl . $path, '/')) ? 'bg-gray-700 text-white border-r-2 border-aegisz-accent' : '';
$isPrefix    = fn(string $path) => (strpos($currentUri, $baseUrl . $path) === 0) ? 'bg-gray-700 text-white border-r-2 border-aegisz-accent' : '';
$isAdmin     = RoleMiddleware::currentUserHasRole('admin');
$isAnalyst   = RoleMiddleware::currentUserHasRole('analyst');
?>
<aside class="w-64 bg-aegisz-panel border-r border-gray-700 flex flex-col shrink-0">
    <nav class="flex-1 py-4 overflow-y-auto">

        <!-- Operations -->
        <div class="px-4 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Operations</div>

        <a href="<?= Security::e($baseUrl) ?>/operations/map"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/operations/map') ?>">
            <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 4"/></svg>
            Operational Map
            <span class="text-xs bg-aegisz-accent/20 text-aegisz-accent px-1.5 py-0.5 rounded font-normal ml-auto">NCSAM</span>
        </a>

        <a href="<?= Security::e($baseUrl) ?>/"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isActive('/') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>

        <a href="<?= Security::e($baseUrl) ?>/alerts"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/alerts') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Alert Queue
        </a>

        <a href="<?= Security::e($baseUrl) ?>/incidents"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/incidents') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Incidents
        </a>

        <!-- SOC Operations (v0.6.0) -->
        <div class="px-4 mt-6 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
            SOC Operations
            <span class="text-xs bg-aegisz-accent/20 text-aegisz-accent px-1.5 py-0.5 rounded font-normal">v0.6.0</span>
        </div>

        <a href="<?= Security::e($baseUrl) ?>/assets"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/assets') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Assets
        </a>

        <a href="<?= Security::e($baseUrl) ?>/iocs"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/iocs') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            IOCs
        </a>

        <a href="<?= Security::e($baseUrl) ?>/threats"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/threats') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Threat Explorer
        </a>

        <a href="<?= Security::e($baseUrl) ?>/correlations"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/correlations') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            Correlation Explorer
        </a>

        <a href="<?= Security::e($baseUrl) ?>/logs"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/logs') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            System Logs
        </a>

        <a href="<?= Security::e($baseUrl) ?>/status"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/status') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            System Health
        </a>

        <!-- Intelligence -->
        <div class="px-4 mt-6 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
            Intelligence
            <span class="text-xs bg-aegisz-accent/20 text-aegisz-accent px-1.5 py-0.5 rounded font-normal">v0.4.0</span>
        </div>

        <a href="<?= Security::e($baseUrl) ?>/intelligence"
           class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/intelligence') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Intelligence
        </a>

        <!-- Admin (admin role only) -->
        <?php if ($isAdmin): ?>
            <div class="px-4 mt-6 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                Administration
                <span class="text-xs bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-normal">Admin</span>
            </div>
            <a href="<?= Security::e($baseUrl) ?>/admin/users"
               class="flex items-center gap-3 px-4 py-2.5 text-gray-300 hover:bg-gray-700 hover:text-white transition-colors <?= $isPrefix('/admin/users') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                User Management
            </a>
        <?php endif; ?>

    </nav>

    <!-- Bottom: current user -->
    <div class="p-4 border-t border-gray-700">
        <?php $user = \App\Core\Session::getUser(); ?>
        <?php if ($user): ?>
            <div class="flex items-center gap-2 mb-2">
                <div class="w-7 h-7 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-300 shrink-0">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <div class="text-sm text-white truncate"><?= Security::e($user['username']) ?></div>
                    <div class="text-xs text-gray-500"><?= Security::e(ucfirst($user['role'])) ?></div>
                </div>
            </div>
        <?php endif; ?>
        <div class="text-xs text-gray-600">&copy; <?= date('Y') ?> AegisZ Sentinel</div>
    </div>
</aside>
