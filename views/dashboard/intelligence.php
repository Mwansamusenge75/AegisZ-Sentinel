<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AegisZ Sentinel v0.4 — SOC Intelligence</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        soc: { bg: '#0b1120', panel: '#111827', border: '#1f2937' },
                        sev: { critical: '#dc2626', high: '#ea580c', medium: '#ca8a04', low: '#16a34a' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #0b1120; color: #e5e7eb; }
        .panel { background: #111827; border: 1px solid #1f2937; border-radius: .5rem; }
        .pulse-red { animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.4)} 50%{box-shadow:0 0 0 8px rgba(220,38,38,0)} }
        .row-hover:hover { background: #1f2937; }
        .scrollbar::-webkit-scrollbar { width: 6px; }
        .scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 3px; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen">

<header class="panel sticky top-0 z-50 border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></div>
            <h1 class="text-lg font-bold tracking-widest text-white">AEGISZ <span class="text-blue-500">SENTINEL</span> <span class="text-xs align-top opacity-70">v0.4</span></h1>
            <span class="text-[10px] bg-blue-900 text-blue-200 px-2 py-0.5 rounded uppercase tracking-wider">Intelligence Mode</span>
        </div>
        <div class="text-xs font-mono text-gray-400" id="utcClock">--:--:-- UTC</div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    <section class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="panel p-6 lg:col-span-1 flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Security Posture</h3>
                <div class="flex items-end gap-2">
                    <span class="text-6xl font-bold <?= $riskPanel['score'] < 40 ? 'text-sev-critical' : ($riskPanel['score'] < 70 ? 'text-sev-medium' : 'text-sev-low') ?>">
                        <?= $riskPanel['score'] ?>
                    </span>
                    <span class="text-xl text-gray-600 mb-2">/100</span>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="px-2 py-1 rounded text-xs font-bold bg-opacity-20 border border-opacity-30
                        <?= $riskPanel['score'] < 40 ? 'bg-red-500 text-red-300 border-red-500' : ($riskPanel['score'] < 70 ? 'bg-yellow-500 text-yellow-300 border-yellow-500' : 'bg-green-500 text-green-300 border-green-500') ?>">
                        GRADE <?= $riskPanel['grade'] ?>
                    </span>
                    <span class="text-xs text-gray-500"><?= $riskPanel['status'] ?></span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-800 text-[11px] text-gray-400 space-y-1">
                <?php foreach ($riskPanel['breakdown'] as $b): ?>
                    <div class="flex justify-between">
                        <span><?= $b['factor'] ?></span>
                        <span class="<?= $b['impact'] < 0 ? 'text-red-400' : 'text-green-400' ?>">
                            <?= ($b['impact'] > 0 ? '+' : '') . $b['impact'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel p-6 lg:col-span-3">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Threat Landscape (30 Days)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php
                $sevMeta = [
                    'Critical' => ['color' => 'bg-red-600', 'text' => 'text-red-400'],
                    'High'     => ['color' => 'bg-orange-600', 'text' => 'text-orange-400'],
                    'Medium'   => ['color' => 'bg-yellow-600', 'text' => 'text-yellow-400'],
                    'Low'      => ['color' => 'bg-green-600', 'text' => 'text-green-400'],
                ];
                foreach ($sevMeta as $sev => $meta):
                    $count = $threatPanel['severity_counts'][$sev] ?? 0;
                    $pct = $threatPanel['total_correlated'] > 0 
                        ? round(($count / $threatPanel['total_correlated']) * 100, 1) 
                        : 0;
                ?>
                    <div class="text-center p-4 rounded bg-gray-900 border border-gray-800">
                        <div class="text-3xl font-bold text-white mb-1"><?= $count ?></div>
                        <div class="text-xs uppercase tracking-wider text-gray-500 mb-2"><?= $sev ?></div>
                        <div class="w-full h-1.5 bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full <?= $meta['color'] ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                        <div class="text-[10px] text-gray-600 mt-1"><?= $pct ?>%</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">

            <div class="panel p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <span>🧠</span> Correlated Threat Intelligence
                    </h3>
                    <span class="text-xs text-gray-600"><?= count($threatPanel['recent_threats']) ?> active correlations</span>
                </div>

                <div class="space-y-3">
                    <?php foreach ($threatPanel['recent_threats'] as $threat): ?>
                        <div class="row-hover p-4 rounded border border-gray-800 transition-colors cursor-pointer group">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center gap-2">
                                    <?php 
                                    $dotClass = match($threat->getSeverity()) {
                                        'Critical' => 'bg-sev-critical pulse-red',
                                        'High'     => 'bg-sev-high',
                                        'Medium'   => 'bg-sev-medium',
                                        default    => 'bg-sev-low',
                                    };
                                    ?>
                                    <span class="w-2 h-2 rounded-full <?= $dotClass ?>"></span>
                                    <span class="font-semibold text-sm text-white group-hover:text-blue-400 transition-colors">
                                        <?= htmlspecialchars($threat->getTitle()) ?>
                                    </span>
                                </div>
                                <span class="text-[10px] px-2 py-1 rounded bg-gray-800 text-gray-300 border border-gray-700">
                                    <?= $threat->getConfidenceScore() ?>% confidence
                                </span>
                            </div>

                            <div class="grid grid-cols-3 gap-4 text-[11px] text-gray-500 mb-3">
                                <div><span class="text-gray-600">Assets:</span> <?= count($threat->getAffectedAssets()) ?></div>
                                <div><span class="text-gray-600">IOCs:</span> <?= count($threat->getRelatedIocs()) ?></div>
                                <div><span class="text-gray-600">CVEs:</span> <?= count($threat->getRelatedCves()) ?></div>
                            </div>

                            <div class="flex flex-wrap gap-2 mb-3">
                                <?php foreach ($threat->getMitreTechniques() as $tech): ?>
                                    <span class="px-2 py-0.5 rounded bg-blue-900/40 text-blue-300 text-[10px] border border-blue-800/50 font-mono">
                                        <?= $tech['technique_id'] ?? 'T0000' ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-[11px] text-gray-400 bg-gray-900/50 p-3 rounded border-l-2 border-blue-600">
                                <span class="text-blue-400 font-semibold uppercase text-[10px]">Recommended Action:</span>
                                <?= htmlspecialchars($threat->getRecommendedAction()) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($threatPanel['recent_threats'])): ?>
                        <div class="text-center py-8 text-gray-600 text-sm">No correlated threats detected. Run ingestion + intelligence worker.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Top Affected Assets</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[10px] text-gray-500 uppercase bg-gray-800/50">
                            <tr>
                                <th class="px-3 py-2">Asset</th>
                                <th class="px-3 py-2">IP</th>
                                <th class="px-3 py-2">Critical</th>
                                <th class="px-3 py-2 text-right">Threats</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-400 text-xs divide-y divide-gray-800">
                            <?php foreach ($exposurePanel as $asset): ?>
                                <tr class="row-hover">
                                    <td class="px-3 py-2 font-medium text-gray-300"><?= htmlspecialchars($asset['name']) ?></td>
                                    <td class="px-3 py-2 font-mono text-gray-500"><?= htmlspecialchars($asset['ip']) ?></td>
                                    <td class="px-3 py-2">
                                        <?php if ($asset['is_critical']): ?>
                                            <span class="text-sev-critical font-bold text-[10px]">CRITICAL</span>
                                        <?php else: ?>
                                            <span class="text-gray-600 text-[10px]">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <span class="inline-block px-2 py-0.5 rounded bg-red-900/30 text-red-300 text-[10px] border border-red-900/50">
                                            <?= $asset['threat_count'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">

            <div class="panel p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-semibold text-red-400 uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        Alert Feed
                    </h3>
                    <span class="text-[10px] text-gray-600 font-mono">LIVE</span>
                </div>

                <div class="space-y-3 max-h-[28rem] overflow-y-auto scrollbar pr-1">
                    <?php foreach ($alertFeed as $alert): ?>
                        <?php 
                        $border = match($alert['severity']) {
                            'Critical' => 'border-red-500',
                            'High'     => 'border-orange-500',
                            default    => 'border-yellow-500',
                        };
                        ?>
                        <div class="p-3 rounded bg-gray-800/50 border-l-4 <?= $border ?>">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-[10px] font-bold text-gray-300 uppercase tracking-wider"><?= $alert['type'] ?></span>
                                <span class="text-[10px] text-gray-600 font-mono"><?= date('H:i', strtotime($alert['created_at'])) ?></span>
                            </div>
                            <p class="text-[11px] text-gray-400 mb-2 leading-relaxed">
                                <?= htmlspecialchars(mb_strimwidth($alert['message'], 0, 140, '...')) ?>
                            </p>
                            <?php if ($alert['asset_name']): ?>
                                <div class="text-[10px] text-gray-500 mb-2">
                                    Asset: <span class="text-blue-400"><?= htmlspecialchars($alert['asset_name']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flex gap-2">
                                <button onclick="act(<?= $alert['id'] ?>,'ack')" class="text-[10px] px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-white transition-colors">Ack</button>
                                <button onclick="act(<?= $alert['id'] ?>,'resolve')" class="text-[10px] px-2 py-1 bg-green-900/40 hover:bg-green-900/60 border border-green-800 rounded text-green-300 transition-colors">Resolve</button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($alertFeed)): ?>
                        <div class="text-center py-6 text-gray-600 text-xs">No open alerts. System nominal.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="text-xs font-semibold text-purple-400 uppercase tracking-wider mb-3">Active Attack Patterns</h3>
                <div class="space-y-2">
                    <?php foreach ($mitrePanel as $tech): ?>
                        <div class="flex items-center justify-between p-3 rounded bg-gray-800/40 border border-gray-800">
                            <div>
                                <div class="text-xs font-bold text-white font-mono"><?= $tech['id'] ?></div>
                                <div class="text-[11px] text-gray-400"><?= $tech['name'] ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-purple-400 leading-none"><?= $tech['count'] ?></div>
                                <div class="text-[10px] text-gray-600">correlations</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </section>
</main>

<script>
    function tick() {
        const now = new Date();
        document.getElementById('utcClock').textContent = now.toISOString().split('T')[1].split('.')[0] + ' UTC';
    }
    setInterval(tick, 1000);
    tick();

    async function act(id, action) {
        const endpoint = action === 'ack' ? `/api/alerts/${id}/acknowledge` : `/api/alerts/${id}/resolve`;
        try {
            await fetch(endpoint, { method: 'POST' });
            location.reload();
        } catch (e) {
            alert('Action failed: ' + e.message);
        }
    }

    setTimeout(() => location.reload(), 60000);
</script>

</body>
</html>
