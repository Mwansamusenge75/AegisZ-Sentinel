<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \App\Core\Security::e($title ?? 'AegisZ Sentinel') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= \App\Core\Security::e($baseUrl ?? '/aegisz-sentinel') ?>/assets/css/aegisz.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'aegisz-dark':    '#0f172a',
                        'aegisz-panel':   '#1e293b',
                        'aegisz-accent':  '#0ea5e9',
                        'aegisz-success': '#10b981',
                        'aegisz-warning': '#f59e0b',
                        'aegisz-danger':  '#ef4444',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-aegisz-dark text-gray-300 font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <?php $view->partial('navbar', [
        'appName'     => $appName     ?? 'AegisZ Sentinel',
        'currentUser' => $currentUser ?? null,
        'baseUrl'     => $baseUrl     ?? '/aegisz-sentinel',
    ]); ?>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <?php $view->partial('sidebar', [
            'appName'     => $appName     ?? 'AegisZ Sentinel',
            'currentUser' => $currentUser ?? null,
            'baseUrl'     => $baseUrl     ?? '/aegisz-sentinel',
        ]); ?>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-6">

            <!-- Global flash messages (rendered here so every page gets them) -->
            <?php if (!empty($flashSuccess)): ?>
                <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= \App\Core\Security::e($flashSuccess) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($flashError)): ?>
                <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= \App\Core\Security::e($flashError) ?>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <script src="<?= \App\Core\Security::e($baseUrl ?? '/aegisz-sentinel') ?>/assets/js/aegisz.js"></script>
</body>
</html>
