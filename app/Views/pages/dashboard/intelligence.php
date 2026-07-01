<?php
$view->partial('components/navbar');
$view->partial('components/sidebar');
$intel = isset($intelData) ? $intelData : [];
?>

<div class="p-6 ml-64 mt-16">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-white">Intelligence Center</h1>
        <span class="text-xs text-gray-500">
            <?php echo isset($intel['cached_at']) ? 'Cached: ' . \App\Core\Security::e($intel['cached_at']) : ''; ?>
        </span>
    </div>

    <!-- Row 1: Risk Score & Grade -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700 lg:col-span-2">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-gray-400 text-sm uppercase">Security Posture Score</div>
                    <?php if (isset($intel['latest_score']) && $intel['latest_score']): ?>
                        <?php $score = (int)$intel['latest_score']['score']; ?>
                        <div class="text-5xl font-bold mt-2 <?php echo $score >= 80 ? 'text-green-400' : ($score >= 60 ? 'text-yellow-400' : 'text-red-400'); ?>">
                            <?php echo $score; ?><span class="text-2xl text-gray-500">/100</span>
                        </div>
                        <div class="text-lg text-gray-300 mt-1">Grade: <span class="font-bold"><?php echo \App\Core\Security::e($intel['latest_score']['grade'] ?? 'N/A'); ?></span></div>
                    <?php else: ?>
                        <div class="text-3xl text-gray-500 mt-2">No score calculated</div>
                        <div class="text-sm text-gray-600 mt-1">Run: php app/Workers/IntelligenceWorker.php</div>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <div class="text-xs text-gray-500 uppercase">Last Calculation</div>
                    <div class="text-sm text-gray-300">
                        <?php echo (isset($intel['latest_score']) && $intel['latest_score']) ? \App\Core\Security::e($intel['latest_score']['calculated_at'] ?? 'N/A') : 'N/A'; ?>
                    </div>
                </div>
            </div>

            <?php if (isset($intel['latest_score']) && $intel['latest_score'] && !empty($intel['latest_score']['breakdown'])): ?>
                <div class="border-t border-gray-700 pt-4 mt-4">
                    <div class="text-gray-400 text-sm uppercase mb-3">Score Breakdown</div>
                    <div class="space-y-2">
                        <?php foreach ($intel['latest_score']['breakdown'] as $deduction): ?>
                            <div class="flex items-center justify-between bg-gray-900 rounded p-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-red-400 font-mono text-sm">-<?php echo abs((int)($deduction['deduction'] ?? 0)); ?></span>
                                    <span class="text-gray-300 text-sm"><?php echo \App\Core\Security::e($deduction['label'] ?? 'Unknown'); ?></span>
                                    <span class="text-gray-500 text-xs">(<?php echo (int)($deduction['count'] ?? 0); ?>)</span>
                                </div>
                                <span class="text-gray-500 text-xs max-w-xs truncate"><?php echo \App\Core\Security::e($deduction['explanation'] ?? ''); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Score Trend -->
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-gray-400 text-sm uppercase mb-3">Score Trend (24h)</div>
            <?php if (isset($intel['score_history']) && !empty($intel['score_history'])): ?>
                <div class="flex items-end gap-1 h-40">
                    <?php
                    $scores = array_column($intel['score_history'], 'score');
                    $maxScore = !empty($scores) ? max($scores) : 100;
                    $minScore = !empty($scores) ? min($scores) : 0;
                    $range = max(1, $maxScore - $minScore);
                    foreach ($intel['score_history'] as $pt):
                        $ptScore = (int)($pt['score'] ?? 0);
                        $height = $range > 0 ? (($ptScore - $minScore) / $range) * 100 : 50;
                        $color = $ptScore >= 80 ? 'bg-green-500' : ($ptScore >= 60 ? 'bg-yellow-500' : 'bg-red-500');
                    ?>
                        <div class="flex-1 flex flex-col justify-end items-center group">
                            <div class="<?php echo $color; ?> w-full rounded-t opacity-80 hover:opacity-100 transition-opacity" style="height: <?php echo max(5, $height); ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex justify-between text-xs text-gray-600 mt-2">
                    <span><?php echo count($intel['score_history']); ?> readings</span>
                    <span>Latest: <?php echo !empty($intel['score_history']) ? (int)end($intel['score_history'])['score'] : 'N/A'; ?></span>
                </div>
            <?php else: ?>
                <div class="text-gray-500 text-sm h-40 flex items-center justify-center">No history available</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row 2: Correlations & MITRE -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Recent Correlations -->
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold text-white">Recent Correlations</h2>
                <span class="text-xs text-gray-500">Last 24h</span>
            </div>
            <?php if (isset($intel['recent_correlations']) && !empty($intel['recent_correlations'])): ?>
                <div class="space-y-2">
                    <?php foreach ($intel['recent_correlations'] as $corr): ?>
                        <div class="bg-gray-900 rounded p-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-300 uppercase"><?php echo \App\Core\Security::e(str_replace('_', ' ', $corr['correlation_type'] ?? 'unknown')); ?></span>
                                    <div class="text-sm text-gray-300 mt-1"><?php echo \App\Core\Security::e(substr($corr['explanation'] ?? '', 0, 120)); ?>...</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-mono text-cyan-400"><?php echo (int)($corr['confidence'] ?? 0); ?>%</div>
                                    <?php $sev = $corr['severity'] ?? 'low'; ?>
                                    <?php if ($sev === 'critical'): ?>
                                        <span class="text-xs text-red-400">CRIT</span>
                                    <?php elseif ($sev === 'high'): ?>
                                        <span class="text-xs text-orange-400">HIGH</span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500"><?php echo \App\Core\Security::e(strtoupper($sev)); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-gray-500 text-sm">No correlations found. Run the Intelligence Worker.</div>
            <?php endif; ?>
        </div>

        <!-- MITRE Technique Distribution -->
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="flex justify-between items-center mb-3">
                <h2 class="text-lg font-semibold text-white">MITRE ATT&CK Distribution</h2>
            </div>
            <?php if (isset($intel['technique_distribution']) && !empty($intel['technique_distribution'])): ?>
                <div class="space-y-3">
                    <?php
                    $maxCount = 0;
                    foreach ($intel['technique_distribution'] as $tech) {
                        $maxCount = max($maxCount, (int)($tech['count'] ?? 0));
                    }
                    ?>
                    <?php foreach ($intel['technique_distribution'] as $tech): ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-300"><?php echo \App\Core\Security::e($tech['technique_id'] ?? 'T0000'); ?> - <?php echo \App\Core\Security::e($tech['technique_name'] ?? 'Unknown'); ?></span>
                                <span class="text-gray-400 font-mono"><?php echo (int)($tech['count'] ?? 0); ?></span>
                            </div>
                            <div class="w-full bg-gray-900 rounded-full h-2">
                                <?php $pct = $maxCount > 0 ? ((int)($tech['count'] ?? 0) / $maxCount) * 100 : 0; ?>
                                <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-gray-500 text-sm">No MITRE mappings found. Run the Intelligence Worker.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row 3: Feed Health & Recent Mappings -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <h2 class="text-lg font-semibold text-white mb-3">Feed Health</h2>
            <div class="space-y-2">
                <div class="flex justify-between items-center p-2 bg-gray-900 rounded">
                    <span class="text-gray-300 text-sm">URLHaus</span>
                    <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-400">Active</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-900 rounded">
                    <span class="text-gray-300 text-sm">AlienVault OTX</span>
                    <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-400">Active</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-900 rounded">
                    <span class="text-gray-300 text-sm">NVD (NIST)</span>
                    <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-400">Active</span>
                </div>
                <div class="flex justify-between items-center p-2 bg-gray-900 rounded">
                    <span class="text-gray-300 text-sm">CISA KEV</span>
                    <span class="text-xs px-2 py-1 rounded bg-green-900 text-green-400">Active</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <h2 class="text-lg font-semibold text-white mb-3">Recent MITRE Mappings</h2>
            <?php if (isset($intel['recent_mitre']) && !empty($intel['recent_mitre'])): ?>
                <div class="space-y-2">
                    <?php foreach ($intel['recent_mitre'] as $map): ?>
                        <div class="flex justify-between items-center p-2 bg-gray-900 rounded">
                            <div>
                                <span class="text-purple-400 font-mono text-sm"><?php echo \App\Core\Security::e($map['technique_id'] ?? 'T0000'); ?></span>
                                <span class="text-gray-300 text-sm ml-2"><?php echo \App\Core\Security::e($map['technique_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo (int)($map['confidence'] ?? 0); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-gray-500 text-sm">No recent mappings.</div>
            <?php endif; ?>
        </div>
    </div>
</div>