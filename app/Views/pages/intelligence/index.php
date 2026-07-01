<?php
/**
 * AegisZ Sentinel - Intelligence Page (v0.4.0)
 * Full intelligence view: correlations, risk score, MITRE technique registry.
 */
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <svg class="w-7 h-7 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Threat Intelligence
            </h1>
            <p class="text-gray-400 text-sm mt-1">Correlations, Risk Scoring &amp; MITRE ATT&amp;CK Mapping — v0.4.0</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-500">Run to update:</div>
            <code class="text-xs text-aegisz-accent bg-gray-800 px-2 py-1 rounded font-mono">php cli/intelligence_worker.php</code>
        </div>
    </div>

    <!-- Security Posture Score + Score History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <!-- Score Panel -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
            <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Security Posture Score
            </h2>
            <?php if (!empty($latestScore)): ?>
                <?php
                    $score = (int) $latestScore['score'];
                    $rating = $latestScore['rating'];
                    $scoreColor = 'aegisz-success';
                    if ($score < 40) $scoreColor = 'aegisz-danger';
                    elseif ($score < 60) $scoreColor = 'aegisz-warning';
                    elseif ($score < 80) $scoreColor = 'yellow';
                    $breakdown = $latestScore['breakdown'] ?? [];
                ?>
                <div class="flex items-end gap-4 mb-4">
                    <div class="text-6xl font-bold text-<?= $scoreColor ?>-400"><?= $score ?></div>
                    <div class="pb-2">
                        <div class="text-white text-base font-semibold"><?= \App\Core\Security::e($rating) ?></div>
                        <div class="text-gray-500 text-xs">out of 100</div>
                    </div>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-3 mb-5">
                    <div class="h-3 rounded-full bg-<?= $scoreColor ?>-500" style="width: <?= $score ?>%"></div>
                </div>
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-3">Breakdown</div>
                <?php if (!empty($breakdown)): ?>
                    <div class="space-y-3">
                        <?php foreach ($breakdown as $item): ?>
                            <div class="p-3 bg-gray-800/60 rounded border border-gray-700/50">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-gray-200 font-medium"><?= \App\Core\Security::e($item['factor']) ?></span>
                                    <span class="text-sm font-mono text-aegisz-danger-400"><?= (int) $item['deduction'] ?></span>
                                </div>
                                <div class="text-xs text-gray-500 leading-snug"><?= \App\Core\Security::e($item['explanation']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-xs text-aegisz-success-400 p-3 bg-aegisz-success-900/20 rounded">No deductions — security posture is clean.</div>
                <?php endif; ?>
                <div class="mt-4 text-xs text-gray-600">Calculated: <?= \App\Core\Security::e($latestScore['created_at'] ?? 'N/A') ?></div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-40 text-center">
                    <div class="text-5xl font-bold text-gray-600 mb-2">—</div>
                    <p class="text-gray-500 text-sm">Not yet calculated</p>
                    <p class="text-gray-600 text-xs mt-1">Run the Intelligence Worker to generate a score</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Score History / Trend -->
        <div class="lg:col-span-2 bg-aegisz-panel border border-gray-700 rounded-lg p-6">
            <h2 class="text-white font-semibold mb-4">Score History (Last <?= count($scoreHistory) ?> runs)</h2>
            <?php if (!empty($scoreHistory)): ?>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
                    <?php foreach ($scoreHistory as $snap): ?>
                        <?php
                            $s = (int) $snap['score'];
                            $c = 'aegisz-success';
                            if ($s < 40) $c = 'aegisz-danger';
                            elseif ($s < 60) $c = 'aegisz-warning';
                            elseif ($s < 80) $c = 'yellow';
                        ?>
                        <div class="flex items-center gap-3 p-2 bg-gray-800/40 rounded">
                            <div class="w-10 text-right text-sm font-bold text-<?= $c ?>-400"><?= $s ?></div>
                            <div class="flex-1 bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full bg-<?= $c ?>-500" style="width: <?= $s ?>%"></div>
                            </div>
                            <div class="text-xs text-gray-500 w-32 text-right"><?= \App\Core\Security::e($snap['created_at']) ?></div>
                            <div class="text-xs text-gray-400 w-20"><?= \App\Core\Security::e($snap['rating']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex items-center justify-center h-40 text-gray-500 text-sm">No score history available yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Threat Correlations -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Threat Correlations
                <span class="text-xs text-gray-500 font-normal">(most recent 25)</span>
            </h2>
            <?php if (!empty($correlationsBySeverity)): ?>
                <div class="flex gap-2 text-xs">
                    <?php foreach (['critical' => 'aegisz-danger', 'high' => 'orange', 'medium' => 'aegisz-warning', 'low' => 'gray'] as $sev => $col): ?>
                        <?php if (!empty($correlationsBySeverity[$sev])): ?>
                            <span class="px-2 py-0.5 rounded bg-<?= $col ?>-900/30 text-<?= $col ?>-400">
                                <?= (int) $correlationsBySeverity[$sev] ?> <?= $sev ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($recentCorrelations)): ?>
            <div class="space-y-3">
                <?php foreach ($recentCorrelations as $corr): ?>
                    <?php
                        $sevColor = 'gray';
                        if ($corr['severity'] === 'critical') $sevColor = 'aegisz-danger';
                        elseif ($corr['severity'] === 'high')  $sevColor = 'orange';
                        elseif ($corr['severity'] === 'medium') $sevColor = 'aegisz-warning';

                        $typeLabel = str_replace('_', ' → ', strtoupper($corr['correlation_type'] ?? ''));
                    ?>
                    <div class="p-4 bg-gray-800/50 rounded border border-gray-700/50">
                        <div class="flex items-start justify-between gap-4 mb-2">
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="text-xs font-mono text-aegisz-accent bg-aegisz-accent/10 px-2 py-0.5 rounded"><?= \App\Core\Security::e($typeLabel) ?></span>
                                <?php if (!empty($corr['ioc_value'])): ?>
                                    <span class="text-xs font-mono text-gray-300"><?= \App\Core\Security::e(substr($corr['ioc_value'], 0, 50)) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($corr['asset_name'])): ?>
                                    <span class="text-xs text-gray-400">→ Asset: <span class="text-white"><?= \App\Core\Security::e($corr['asset_name']) ?></span></span>
                                <?php endif; ?>
                                <?php if (!empty($corr['source_feed'])): ?>
                                    <span class="text-xs text-gray-500">via <?= \App\Core\Security::e($corr['source_feed']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="flex items-center gap-1 text-xs text-gray-400">
                                    <div class="w-16 bg-gray-700 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full bg-aegisz-accent" style="width:<?= (int)$corr['confidence'] ?>%"></div>
                                    </div>
                                    <?= (int) $corr['confidence'] ?>%
                                </div>
                                <span class="px-2 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400">
                                    <?= \App\Core\Security::e(ucfirst($corr['severity'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 leading-relaxed"><?= \App\Core\Security::e($corr['explanation']) ?></div>
                        <div class="mt-2 text-xs text-gray-600"><?= \App\Core\Security::e($corr['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center h-32 text-center">
                <p class="text-gray-500 text-sm">No correlations yet.</p>
                <p class="text-gray-600 text-xs mt-1">Run <code class="text-aegisz-accent">php cli/intelligence_worker.php</code> to generate correlations.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- MITRE ATT&CK Distribution + Registry -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <!-- Distribution -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
            <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                MITRE ATT&amp;CK Distribution
            </h2>
            <?php if (!empty($mitreDistribution)): ?>
                <?php $maxCount = max(array_column($mitreDistribution, 'count')); ?>
                <div class="space-y-3">
                    <?php foreach ($mitreDistribution as $entry): ?>
                        <?php $barWidth = $maxCount > 0 ? round(($entry['count'] / $maxCount) * 100) : 0; ?>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <span class="text-xs font-mono text-aegisz-accent shrink-0"><?= \App\Core\Security::e($entry['technique_id']) ?></span>
                                    <span class="text-sm text-gray-200 truncate"><?= \App\Core\Security::e($entry['technique_name']) ?></span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 ml-2">
                                    <span class="text-xs text-gray-500"><?= \App\Core\Security::e($entry['tactic']) ?></span>
                                    <span class="text-sm font-mono text-gray-300"><?= (int) $entry['count'] ?></span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-aegisz-accent" style="width: <?= $barWidth ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-gray-500 text-sm text-center py-8">No techniques mapped yet.</div>
            <?php endif; ?>
        </div>

        <!-- Technique Registry -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-6">
            <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Supported Techniques
            </h2>
            <div class="space-y-2">
                <?php foreach ($mitreRegistry as $tech): ?>
                    <div class="flex items-center justify-between p-2 bg-gray-800/40 rounded">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-xs font-mono text-aegisz-accent w-14 shrink-0"><?= \App\Core\Security::e($tech['id']) ?></span>
                            <span class="text-sm text-gray-200 truncate"><?= \App\Core\Security::e($tech['name']) ?></span>
                        </div>
                        <span class="text-xs text-gray-500 shrink-0 ml-2"><?= \App\Core\Security::e($tech['tactic']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-4 border-t border-gray-700 pt-4 text-xs text-gray-500 flex justify-between items-center">
        <div>AegisZ Sentinel v<?= \App\Core\Security::e($version) ?> &mdash; Intelligence Platform</div>
        <div>PHP <?= phpversion() ?></div>
    </div>
</div>
