<?php use App\Core\Security; ?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Analyst Dashboard</h1>
        <p class="text-gray-400 text-sm mt-1">Your assigned work and the latest intelligence</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Security Score -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Security Posture Score</div>
            <?php if ($latestScore): ?>
                <?php $s=(int)$latestScore['score']; $c=$s<40?'aegisz-danger':($s<60?'aegisz-warning':($s<80?'yellow':'aegisz-success')); ?>
                <div class="text-3xl font-bold text-<?= $c ?>-400"><?= $s ?>/100</div>
                <div class="text-xs text-gray-500"><?= Security::e($latestScore['rating']) ?></div>
            <?php else: ?><div class="text-3xl font-bold text-gray-600">—</div><?php endif; ?>
        </div>
        <a href="<?= Security::e($baseUrl) ?>/alerts" class="bg-aegisz-panel border border-gray-700 hover:border-aegisz-accent rounded-lg p-5 transition-colors">
            <div class="text-gray-400 text-sm font-medium mb-2">My Assigned Alerts</div>
            <div class="text-3xl font-bold text-white"><?= count($assignedAlerts) ?></div>
        </a>
        <a href="<?= Security::e($baseUrl) ?>/incidents" class="bg-aegisz-panel border border-gray-700 hover:border-aegisz-accent rounded-lg p-5 transition-colors">
            <div class="text-gray-400 text-sm font-medium mb-2">My Assigned Incidents</div>
            <div class="text-3xl font-bold text-white"><?= count($assignedIncidents) ?></div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Assigned Alerts -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4 flex items-center justify-between">My Alerts <a href="<?= Security::e($baseUrl) ?>/alerts" class="text-xs text-aegisz-accent hover:underline font-normal">View All</a></h2>
            <?php if (empty($assignedAlerts)): ?>
                <p class="text-gray-500 text-sm text-center py-6">No alerts assigned. Showing none open.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($assignedAlerts,0,6) as $a): ?>
                        <?php $sev=['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$a['severity']]??'gray'; ?>
                        <div class="flex items-center justify-between p-2.5 bg-gray-800/50 rounded">
                            <span class="text-sm text-gray-200 truncate"><?= Security::e($a['title']) ?></span>
                            <span class="px-2 py-0.5 rounded text-xs bg-<?= $sev ?>-900/30 text-<?= $sev ?>-400 shrink-0 ml-2"><?= Security::e(ucfirst($a['severity'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assigned Incidents -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4 flex items-center justify-between">My Incidents <a href="<?= Security::e($baseUrl) ?>/incidents" class="text-xs text-aegisz-accent hover:underline font-normal">View All</a></h2>
            <?php if (empty($assignedIncidents)): ?>
                <p class="text-gray-500 text-sm text-center py-6">No incidents assigned. Showing none open.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($assignedIncidents,0,6) as $i): ?>
                        <a href="<?= Security::e($baseUrl) ?>/incidents/detail?id=<?= (int)$i['id'] ?>" class="flex items-center justify-between p-2.5 bg-gray-800/50 hover:bg-gray-700/50 rounded transition-colors">
                            <span class="text-sm text-gray-200 truncate"><?= Security::e($i['title']) ?></span>
                            <span class="text-xs text-gray-500 shrink-0 ml-2"><?= Security::e(ucfirst($i['status'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- High Risk Assets -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4">High Risk Assets</h2>
            <?php if (empty($highRiskAssets)): ?>
                <p class="text-gray-500 text-sm">No critical assets with active correlations.</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($highRiskAssets as $a): ?>
                        <a href="<?= Security::e($baseUrl) ?>/assets/detail?id=<?= (int)$a['id'] ?>" class="flex items-center justify-between p-2 bg-gray-800/50 hover:bg-gray-700/50 rounded transition-colors">
                            <span class="text-sm text-gray-200 truncate"><?= Security::e($a['name']) ?></span>
                            <span class="text-xs text-aegisz-danger-400 shrink-0"><?= (int)$a['correlation_count'] ?> corr.</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Latest Threats -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4 flex items-center justify-between">Latest Threats <a href="<?= Security::e($baseUrl) ?>/threats" class="text-xs text-aegisz-accent hover:underline font-normal">Explorer</a></h2>
            <div class="space-y-2">
                <?php foreach (array_slice($latestThreats,0,6) as $t): ?>
                    <?php $sev=['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$t['severity']]??'gray'; ?>
                    <a href="<?= Security::e($baseUrl) ?>/threats/detail?id=<?= (int)$t['id'] ?>" class="flex items-center justify-between p-2 bg-gray-800/50 hover:bg-gray-700/50 rounded transition-colors">
                        <span class="text-sm text-gray-200 truncate"><?= Security::e(substr($t['title'],0,40)) ?></span>
                        <span class="px-1.5 py-0.5 rounded text-xs bg-<?= $sev ?>-900/30 text-<?= $sev ?>-400 shrink-0 ml-2"><?= Security::e(ucfirst($t['severity'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- IOC Feed -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4 flex items-center justify-between">IOC Feed <a href="<?= Security::e($baseUrl) ?>/iocs" class="text-xs text-aegisz-accent hover:underline font-normal">View All</a></h2>
            <div class="space-y-2">
                <?php foreach (array_slice($recentIOCs,0,6) as $ioc): ?>
                    <a href="<?= Security::e($baseUrl) ?>/iocs/detail?id=<?= $ioc->id ?>" class="flex items-center justify-between p-2 bg-gray-800/50 hover:bg-gray-700/50 rounded transition-colors">
                        <span class="font-mono text-xs text-gray-300 truncate"><?= Security::e(substr($ioc->value,0,30)) ?></span>
                        <span class="text-xs text-gray-500 shrink-0 ml-2"><?= (int)$ioc->confidenceScore ?>%</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- v0.7.0: NCSAM Quick Launch + AI Assessment (analyst) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
        <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            AI National Assessment
        </h2>
        <div id="dash-ai-analyst" class="text-sm text-gray-500">Loading…</div>
    </div>
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5 flex flex-col justify-between">
        <div><h2 class="text-white font-semibold mb-2">Operational Map</h2>
        <p class="text-gray-400 text-sm">View all assets, active alerts, and incidents on the national situational awareness map.</p></div>
        <a href="<?= \App\Core\Security::e($baseUrl) ?>/operations/map"
           class="mt-4 inline-flex items-center justify-center gap-2 bg-aegisz-accent hover:bg-sky-400 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
            Open NCSAM Map
        </a>
    </div>
</div>
<script>
(function(){
    fetch('<?= \App\Core\Security::e($baseUrl) ?>/api/ai/assessment')
        .then(r=>r.json()).then(j=>{
            const el=document.getElementById('dash-ai-analyst');
            if(!j.ai_enabled){el.innerHTML='<span class="text-gray-600">AI not configured.</span>';return;}
            if(!j.success){el.innerHTML='<span class="text-red-400 text-xs">Unavailable</span>';return;}
            const d=j.data;
            const lvlColor={Low:'aegisz-success',Moderate:'aegisz-warning',Elevated:'orange',High:'aegisz-danger',Critical:'aegisz-danger'}[d.threat_level]||'gray';
            el.innerHTML=`<div class="flex items-center gap-2 mb-2"><span class="text-lg font-bold text-${lvlColor}-400">${d.threat_level}</span><span class="text-xs text-gray-500">${d.confidence}%</span></div><p class="text-sm text-gray-300 mb-3">${escH(d.summary)}</p>${d.recommendations.map(r=>`<div class="text-xs text-purple-300 mb-1">→ ${escH(r)}</div>`).join('')}`;
        }).catch(()=>{document.getElementById('dash-ai-analyst').innerHTML='<span class="text-gray-600">AI unavailable.</span>';});
    function escH(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
})();
</script>
