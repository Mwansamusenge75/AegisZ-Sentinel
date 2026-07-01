<?php use App\Core\Security; ?>
<div class="max-w-7xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Intelligence Overview</h1>
        <p class="text-gray-400 text-sm mt-1">Read-only summary of current threat landscape</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Security Posture</div>
            <?php if ($latestScore): ?>
                <?php $s=(int)$latestScore['score']; $c=$s<40?'aegisz-danger':($s<60?'aegisz-warning':($s<80?'yellow':'aegisz-success')); ?>
                <div class="text-2xl font-bold text-<?= $c ?>-400"><?= $s ?>/100</div>
                <div class="text-xs text-gray-500"><?= Security::e($latestScore['rating']) ?></div>
            <?php else: ?><div class="text-2xl font-bold text-gray-600">—</div><?php endif; ?>
        </div>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Assets</div>
            <div class="text-2xl font-bold text-white"><?= number_format($assetCounts['total']) ?></div>
        </div>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">IOCs Tracked</div>
            <div class="text-2xl font-bold text-white"><?= number_format($iocCounts['total']) ?></div>
        </div>
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="text-gray-400 text-sm font-medium mb-2">Threats</div>
            <div class="text-2xl font-bold text-white"><?= number_format($threatCounts['total']) ?></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- MITRE distribution -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4">MITRE ATT&amp;CK Distribution</h2>
            <?php if (!empty($mitreDistribution)): ?>
                <?php $maxCount = max(array_column($mitreDistribution,'count')); ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($mitreDistribution,0,8) as $e): ?>
                        <?php $w = $maxCount>0?round($e['count']/$maxCount*100):0; ?>
                        <div>
                            <div class="flex justify-between text-xs mb-1"><span class="text-gray-300"><?= Security::e($e['technique_name']) ?></span><span class="text-gray-500"><?= (int)$e['count'] ?></span></div>
                            <div class="w-full bg-gray-700 rounded-full h-1.5"><div class="h-1.5 rounded-full bg-aegisz-accent" style="width:<?= $w ?>%"></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><p class="text-gray-500 text-sm">No techniques mapped yet.</p><?php endif; ?>
        </div>

        <!-- Threat trends -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <h2 class="text-white font-semibold mb-4">Latest Threats</h2>
            <?php if (!empty($latestThreats)): ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($latestThreats,0,6) as $t): ?>
                        <?php $sev=['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$t->severity]??'gray'; ?>
                        <div class="flex items-center justify-between p-2 bg-gray-800/50 rounded">
                            <span class="text-sm text-gray-300 truncate"><?= Security::e(substr($t->title,0,45)) ?></span>
                            <span class="px-1.5 py-0.5 rounded text-xs bg-<?= $sev ?>-900/30 text-<?= $sev ?>-400 shrink-0 ml-2"><?= Security::e(ucfirst($t->severity)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><p class="text-gray-500 text-sm">No threats recorded.</p><?php endif; ?>
        </div>
    </div>

    <!-- Recent correlations summary -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-semibold">Recent Correlations</h2>
            <?php if (!empty($correlationsBySeverity)): ?>
                <div class="flex gap-2 text-xs">
                    <?php foreach (['critical'=>'aegisz-danger','high'=>'orange','medium'=>'aegisz-warning','low'=>'gray'] as $sev=>$col): ?>
                        <?php if (!empty($correlationsBySeverity[$sev])): ?><span class="px-2 py-0.5 rounded bg-<?= $col ?>-900/30 text-<?= $col ?>-400"><?= (int)$correlationsBySeverity[$sev] ?> <?= $sev ?></span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (empty($recentCorrelations)): ?>
            <p class="text-gray-500 text-sm text-center py-6">No correlations recorded yet.</p>
        <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($recentCorrelations as $c): ?>
                    <?php $sev=['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$c['severity']]??'gray'; ?>
                    <div class="flex items-center justify-between p-2.5 bg-gray-800/50 rounded">
                        <span class="text-sm text-gray-300 truncate"><?= Security::e(substr($c['explanation'],0,70)) ?></span>
                        <span class="px-1.5 py-0.5 rounded text-xs bg-<?= $sev ?>-900/30 text-<?= $sev ?>-400 shrink-0 ml-2"><?= Security::e(ucfirst($c['severity'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- v0.7.0: AI National Assessment (viewer — read only) + NCSAM link -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
        <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            AI National Assessment
        </h2>
        <div id="dash-ai-viewer" class="text-sm text-gray-500">Loading…</div>
    </div>
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5 flex flex-col justify-between">
        <div><h2 class="text-white font-semibold mb-2">Operational Map</h2>
        <p class="text-gray-400 text-sm">Zambia National Cyber Situational Awareness Map — read-only view.</p></div>
        <a href="<?= \App\Core\Security::e($baseUrl) ?>/operations/map"
           class="mt-4 inline-flex items-center justify-center gap-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
            View NCSAM Map
        </a>
    </div>
</div>
<script>
(function(){
    fetch('<?= \App\Core\Security::e($baseUrl) ?>/api/ai/assessment')
        .then(r=>r.json()).then(j=>{
            const el=document.getElementById('dash-ai-viewer');
            if(!j.ai_enabled){el.innerHTML='<span class="text-gray-600">AI not configured.</span>';return;}
            if(!j.success){el.innerHTML='<span class="text-red-400 text-xs">Unavailable</span>';return;}
            const d=j.data;
            const lvlColor={Low:'aegisz-success',Moderate:'aegisz-warning',Elevated:'orange',High:'aegisz-danger',Critical:'aegisz-danger'}[d.threat_level]||'gray';
            el.innerHTML=`<div class="flex items-center gap-2 mb-2"><span class="text-lg font-bold text-${lvlColor}-400">${d.threat_level}</span><span class="text-xs text-gray-500">${d.confidence}%</span></div><p class="text-sm text-gray-300">${escH(d.summary)}</p>`;
        }).catch(()=>{document.getElementById('dash-ai-viewer').innerHTML='<span class="text-gray-600">AI unavailable.</span>';});
    function escH(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
})();
</script>
