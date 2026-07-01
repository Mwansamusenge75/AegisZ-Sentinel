<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | AegisZ Sentinel</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-aegisz-dark text-gray-300 font-sans antialiased min-h-screen flex items-center justify-center">

    <div class="w-full max-w-md px-4">

        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-aegisz-panel border border-gray-700 rounded-2xl mb-4">
                <svg class="w-8 h-8 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">AegisZ Sentinel</h1>
            <p class="text-gray-500 text-sm mt-1">Zambia Cyber Situational Awareness Platform</p>
        </div>

        <!-- Flash error -->
        <?php if (!empty($flashError)): ?>
            <div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?= \App\Core\Security::e($flashError) ?>
            </div>
        <?php endif; ?>

        <!-- Flash success -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm">
                <?= \App\Core\Security::e($flashSuccess) ?>
            </div>
        <?php endif; ?>

        <!-- Login Card -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-2xl p-8">
            <h2 class="text-lg font-semibold text-white mb-6">Sign In</h2>

            <form method="POST" action="<?= \App\Core\Security::e($baseUrl) ?>/login" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= \App\Core\Security::e($csrfToken) ?>">

                <!-- Username -->
                <div class="mb-5">
                    <label for="username" class="block text-sm font-medium text-gray-400 mb-1.5">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm placeholder-gray-600
                               focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent transition-colors"
                        placeholder="Enter your username"
                    >
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-400 mb-1.5">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2.5 text-white text-sm placeholder-gray-600
                                   focus:outline-none focus:border-aegisz-accent focus:ring-1 focus:ring-aegisz-accent transition-colors pr-10"
                            placeholder="Enter your password"
                        >
                        <!-- Toggle visibility -->
                        <button type="button" id="togglePw"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300 transition-colors">
                            <svg id="eyeIcon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full bg-aegisz-accent hover:bg-sky-400 text-white font-semibold py-2.5 px-4 rounded-lg
                               transition-colors text-sm focus:outline-none focus:ring-2 focus:ring-aegisz-accent focus:ring-offset-2
                               focus:ring-offset-aegisz-panel">
                    Sign In
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-xs text-gray-600">
            AegisZ Sentinel v0.5.0 &mdash; Restricted Access
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePw');
        const pwInput   = document.getElementById('password');
        toggleBtn.addEventListener('click', () => {
            pwInput.type = pwInput.type === 'password' ? 'text' : 'password';
        });
    </script>
</body>
</html>
