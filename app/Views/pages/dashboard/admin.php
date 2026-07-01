<?php use App\Core\Security; ?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Admin Dashboard</h1>
        <p class="text-gray-400 text-sm mt-1">System health, user activity, and platform-wide intelligence overview</p>
    </div>

    <!-- System + User KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">System Status</div>
            <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-<?= $systemStatus['color'] ?>-500"></span><span class="text-lg font-semibold text-white"><?= Security::e($systemStatus['label']) ?></span></div>
            <div class="mt-2 text-xs text-gray-500">DB: <?= $systemStatus['db']?'Connected':'Disconnected' ?> | Logs: <?= $systemStatus['logs']?'Writable':'Read-only' ?></div>
        </div>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Log Events</div>
            <div class="text-2xl font-bold text-white"><?= number_format($logEventsCount) ?></div>
        </div>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Security Posture Score</div>
            <?php if ($latestScore): ?>
                <?php $s=(int)$latestScore['score']; $c=$s<40?'aegisz-danger':($s<60?'aegisz-warning':($s<80?'yellow':'aegisz-success')); ?>
                <div class="text-2xl font-bold text-<?= $c ?>-400"><?= $s ?>/100</div>
                <div class="text-xs text-gray-500"><?= Security::e($latestScore['rating']) ?></div>
            <?php else: ?>
                <div class="text-2xl font-bold text-gray-600">—</div>
            <?php endif; ?>
        </div>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Active Correlations</div>
            <div class="text-2xl font-bold text-white"><?= count($recentCorrelations) ?></div>
            <div class="text-xs text-gray-500">Most recent batch</div>
        </div>
    </div>

    <!-- Cyber object counts -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
        <?php foreach ([
            ['Assets', $assetCounts['total'], $baseUrl.'/assets'],
            ['IOCs', $iocCounts['total'], $baseUrl.'/iocs'],
            ['Threats', $threatCounts['total'], $baseUrl.'/threats'],
            ['Alerts', $alertCounts['total'], $baseUrl.'/alerts'],
            ['Incidents', $incidentCounts['total'], $baseUrl.'/incidents'],
        ] as [$label,$count,$link]): ?>
            <a href="<?= Security::e($link) ?>" class="bg-aegisz-panel border border-gray-700 hover:border-aegisz-accent rounded-lg p-5 transition-colors">
                <div class="text-gray-400 text-sm font-medium mb-2"><?= $label ?></div>
                <div class="text-2xl font-bold text-white"><?= number_format($count) ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Worker / Feed Health -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4">Worker / Feed Health</h2>
            <div class="space-y-2">
                <?php if (empty($ingestionStatus)): ?>
                    <p class="text-gray-500 text-sm text-center py-4">No ingestion runs recorded.</p>
                <?php else: ?>
                    <?php foreach ($ingestionStatus as $status): ?>
                        <?php $sc = $status['status']==='success'?'aegisz-success':($status['status']==='failed'?'aegisz-danger':'aegisz-warning'); ?>
                        <div class="flex items-center justify-between p-2.5 bg-gray-800/50 rounded">
                            <span class="text-sm text-white"><?= Security::e(str_replace('Worker','',$status['worker_name'])) ?></span>
                            <span class="flex items-center gap-1.5 text-xs text-<?= $sc ?>-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span><?= Security::e(ucfirst($status['status'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Logins -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4">Recent Activity</h2>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                <?php foreach (array_slice($recentActivity, 0, 8) as $log): ?>
                    <?php $lc = ($log['level']??'')==='ERROR'?'aegisz-danger':(($log['level']??'')==='WARNING'?'aegisz-warning':'aegisz-success'); ?>
                    <div class="flex items-start gap-2 p-2 bg-gray-800/50 rounded">
                        <span class="w-1.5 h-1.5 rounded-full bg-<?= $lc ?> mt-1.5 shrink-0"></span>
                        <div class="min-w-0">
                            <div class="text-xs text-gray-300 truncate"><?= Security::e($log['message']??'') ?></div>
                            <div class="text-xs text-gray-600"><?= Security::e($log['created_at']??'') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Admin quick link -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-white font-semibold">User Management</h2>
                <p class="text-gray-500 text-sm mt-1">Manage platform users, roles, and access</p>
            </div>
            <a href="<?= Security::e($baseUrl) ?>/admin/users" class="bg-aegisz-accent hover:bg-sky-400 text-white text-sm px-4 py-2 rounded-lg transition-colors">Manage Users</a>
        </div>
    </div>
</div>

<!-- v0.7.0: AI National Assessment Widget + NCSAM Quick Launch -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
    <!-- AI Assessment -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
        <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            AI National Assessment
            <span class="text-xs text-purple-400/60 font-normal">OpenRouter — Advisory</span>
        </h2>
        <div id="dash-ai-assessment" class="text-sm text-gray-500">Loading…</div>
    </div>

    <!-- NCSAM Quick Launch -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5 flex flex-col justify-between">
        <div>
            <h2 class="text-white font-semibold mb-2 flex items-center gap-2">
                <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 4"/></svg>
                Operational Map (NCSAM)
            </h2>
            <p class="text-gray-400 text-sm">National Cyber Situational Awareness Map — real-time threat and asset geo-intelligence for Zambia.</p>
        </div>
        <a href="<?= \App\Core\Security::e($baseUrl) ?>/operations/map"
           class="mt-4 inline-flex items-center justify-center gap-2 bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7"/></svg>
            Open Operational Map
        </a>
    </div>
</div>

<script>
(function() {
    fetch('<?= \App\Core\Security::e($baseUrl) ?>/api/ai/assessment')
        .then(r => r.json())
        .then(j => {
            const el = document.getElementById('dash-ai-assessment');
            if (!j.ai_enabled) { el.innerHTML = '<span class="text-gray-600">AI not configured.</span>'; return; }
            if (!j.success)    { el.innerHTML = '<span class="text-red-400 text-xs">' + (j.message||'Unavailable') + '</span>'; return; }
            const d = j.data;
            const lvlColor = {Low:'aegisz-success',Moderate:'aegisz-warning',Elevated:'orange',High:'aegisz-danger',Critical:'aegisz-danger'}[d.threat_level] || 'gray';
            el.innerHTML = `
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg font-bold text-${lvlColor}-400">${d.threat_level}</span>
                    <span class="text-xs text-gray-500">${d.confidence}% confidence</span>
                </div>
                <p class="text-sm text-gray-300 mb-2">${escH(d.summary)}</p>
                ${d.affected_sectors.length ? '<div class="text-xs text-gray-500">Sectors: ' + d.affected_sectors.map(s=>`<span class="bg-gray-700 px-1.5 py-0.5 rounded ml-1">${escH(s)}</span>`).join('')+'</div>' : ''}`;
        })
        .catch(() => { document.getElementById('dash-ai-assessment').innerHTML = '<span class="text-gray-600">AI unavailable.</span>'; });
    function escH(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
})();
</script>
