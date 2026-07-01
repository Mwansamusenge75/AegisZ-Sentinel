<?php
/**
 * AegisZ Sentinel - 404 Error Page
 */
?>
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="w-20 h-20 bg-gray-700 rounded-full flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>
    <h1 class="text-4xl font-bold text-white mb-2">404</h1>
    <p class="text-gray-400 text-lg mb-6">Page not found or resource unavailable.</p>
    <a href="<?= \App\Core\Security::e($baseUrl ?? '/aegisz-sentinel') ?>/" class="inline-flex items-center gap-2 px-5 py-2.5 bg-aegisz-accent text-white rounded hover:bg-sky-600 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Return to Dashboard
    </a>
</div>
