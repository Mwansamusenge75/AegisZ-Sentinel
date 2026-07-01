<?php
/**
 * AegisZ Sentinel - Dashboard Page (v0.4.0)
 * SOC dashboard: KPIs, cyber object counts, ingestion status,
 * Intelligence Layer (correlations, risk score, MITRE distribution).
 */
?>
<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Security Operations Dashboard</h1>
        <p class="text-gray-400 text-sm mt-1">Zambia Cyber Situational Awareness &amp; Threat Intelligence Platform</p>
    </div>

    <!-- KPI Cards Row 1: System Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">System Status</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-<?= $systemStatus['color'] ?>-500"></span>
                <span class="text-lg font-semibold text-white"><?= \App\Core\Security::e($systemStatus['label']) ?></span>
            </div>
            <div class="mt-2 text-xs text-gray-500">DB: <?= $systemStatus['db'] ? 'Connected' : 'Disconnected' ?> | Logs: <?= $systemStatus['logs'] ? 'Writable' : 'Read-Only' ?></div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Active Connections</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($activeConnections) ?></div>
            <div class="mt-2 text-xs text-gray-500">Placeholder for future real-time data</div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Last Sync</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-lg font-semibold text-white"><?= \App\Core\Security::e($lastSyncTime) ?></div>
            <div class="mt-2 text-xs text-gray-500">Next sync in 5 minutes</div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Log Events</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($logEventsCount) ?></div>
            <div class="mt-2 text-xs text-gray-500">Total audit log entries</div>
        </div>
    </div>

    <!-- KPI Cards Row 2: Cyber Object Counts (v0.2.0) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Assets</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($assetCounts['total']) ?></div>
            <div class="mt-2 text-xs text-gray-500">Tracked infrastructure</div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">IOCs</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($iocCounts['total']) ?></div>
            <div class="mt-2 text-xs text-gray-500">Indicators of compromise</div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Threats</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($threatCounts['total']) ?></div>
            <div class="mt-2 text-xs text-gray-500">Active threat entries</div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Alerts</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($alertCounts['total']) ?></div>
            <div class="mt-2 text-xs text-gray-500">Security alerts</div>
        </div>

        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-gray-400 text-sm font-medium">Incidents</span>
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white"><?= number_format($incidentCounts['total']) ?></div>
            <div class="mt-2 text-xs text-gray-500">Active incidents</div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- INTELLIGENCE LAYER (v0.4.0)                                  -->
    <!-- ============================================================ -->
    <div class="mb-2 mt-6">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider flex items-center gap-2">
            <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            Intelligence Layer — v0.4.0
            <span class="text-xs bg-aegisz-accent/20 text-aegisz-accent px-2 py-0.5 rounded font-normal">NEW</span>
        </h2>
    </div>

    <!-- Row: Security Posture Score + MITRE Distribution -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        <!-- Security Posture Score -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Security Posture Score
                </h2>
            </div>
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
                <div class="flex items-end gap-3 mb-4">
                    <div class="text-5xl font-bold text-<?= $scoreColor ?>-400"><?= $score ?></div>
                    <div class="pb-1">
                        <div class="text-gray-300 text-sm font-medium"><?= \App\Core\Security::e($rating) ?></div>
                        <div class="text-gray-500 text-xs">out of 100</div>
                    </div>
                </div>
                <!-- Score bar -->
                <div class="w-full bg-gray-700 rounded-full h-2 mb-4">
                    <div class="h-2 rounded-full bg-<?= $scoreColor ?>-500 transition-all" style="width: <?= $score ?>%"></div>
                </div>
                <!-- Breakdown -->
                <?php if (!empty($breakdown)): ?>
                    <div class="space-y-2">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Deductions</div>
                        <?php foreach ($breakdown as $item): ?>
                            <div class="flex items-start justify-between gap-2">
                                <div class="text-xs text-gray-300 leading-snug flex-1"><?= \App\Core\Security::e($item['factor']) ?></div>
                                <div class="text-xs text-aegisz-danger-400 font-mono shrink-0"><?= $item['deduction'] ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-xs text-aegisz-success-400">No deductions — posture is clean.</div>
                <?php endif; ?>
                <div class="mt-3 text-xs text-gray-600">Last calculated: <?= \App\Core\Security::e($latestScore['created_at'] ?? 'N/A') ?></div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-32 text-center">
                    <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-gray-500 text-sm">Score not yet calculated</p>
                    <p class="text-gray-600 text-xs mt-1">Run the Intelligence Worker to generate</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- MITRE ATT&CK Distribution -->
        <div class="lg:col-span-2 bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    MITRE ATT&amp;CK Technique Distribution
                </h2>
                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">Keyword Mapped</span>
            </div>
            <?php if (!empty($mitreDistribution)): ?>
                <?php
                    $maxCount = max(array_column($mitreDistribution, 'count'));
                ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($mitreDistribution, 0, 8) as $entry): ?>
                        <?php $barWidth = $maxCount > 0 ? round(($entry['count'] / $maxCount) * 100) : 0; ?>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="text-xs font-mono text-aegisz-accent shrink-0"><?= \App\Core\Security::e($entry['technique_id']) ?></span>
                                    <span class="text-xs text-gray-300 truncate"><?= \App\Core\Security::e($entry['technique_name']) ?></span>
                                    <span class="text-xs text-gray-600 shrink-0">&middot; <?= \App\Core\Security::e($entry['tactic']) ?></span>
                                </div>
                                <span class="text-xs text-gray-400 font-mono shrink-0 ml-2"><?= (int) $entry['count'] ?></span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full bg-aegisz-accent" style="width: <?= $barWidth ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-32 text-center">
                    <svg class="w-8 h-8 text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <p class="text-gray-500 text-sm">No MITRE techniques mapped yet</p>
                    <p class="text-gray-600 text-xs mt-1">Run the Intelligence Worker to map threats</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row: Recent Threat Correlations -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Recent Threat Correlations
            </h2>
            <?php if (!empty($correlationsBySeverity)): ?>
                <div class="flex items-center gap-2 text-xs">
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
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-gray-500 border-b border-gray-700">
                            <th class="pb-2 pr-4 font-medium">Type</th>
                            <th class="pb-2 pr-4 font-medium">IOC / Value</th>
                            <th class="pb-2 pr-4 font-medium">Asset</th>
                            <th class="pb-2 pr-4 font-medium">Source</th>
                            <th class="pb-2 pr-4 font-medium">Confidence</th>
                            <th class="pb-2 pr-4 font-medium">Severity</th>
                            <th class="pb-2 font-medium">Explanation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        <?php foreach ($recentCorrelations as $corr): ?>
                            <?php
                                $sevColor = 'gray';
                                if ($corr['severity'] === 'critical') $sevColor = 'aegisz-danger';
                                elseif ($corr['severity'] === 'high')  $sevColor = 'orange';
                                elseif ($corr['severity'] === 'medium') $sevColor = 'aegisz-warning';

                                $typeLabel = str_replace('_', ' → ', strtoupper($corr['correlation_type'] ?? ''));
                            ?>
                            <tr class="text-gray-300 hover:bg-gray-700/20">
                                <td class="py-2.5 pr-4">
                                    <span class="font-mono text-aegisz-accent text-xs"><?= \App\Core\Security::e($typeLabel) ?></span>
                                </td>
                                <td class="py-2.5 pr-4 font-mono text-gray-400 max-w-xs truncate" title="<?= \App\Core\Security::e($corr['ioc_value'] ?? 'N/A') ?>">
                                    <?= \App\Core\Security::e(substr($corr['ioc_value'] ?? 'N/A', 0, 30)) ?>
                                </td>
                                <td class="py-2.5 pr-4 text-gray-300">
                                    <?= \App\Core\Security::e($corr['asset_name'] ?? '—') ?>
                                </td>
                                <td class="py-2.5 pr-4 text-gray-400">
                                    <?= \App\Core\Security::e($corr['source_feed'] ?? '—') ?>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-12 bg-gray-700 rounded-full h-1">
                                            <div class="h-1 rounded-full bg-aegisz-accent" style="width:<?= (int)$corr['confidence'] ?>%"></div>
                                        </div>
                                        <span class="text-gray-400"><?= (int) $corr['confidence'] ?>%</span>
                                    </div>
                                </td>
                                <td class="py-2.5 pr-4">
                                    <span class="px-1.5 py-0.5 rounded text-xs bg-<?= $sevColor ?>-900/30 text-<?= $sevColor ?>-400">
                                        <?= \App\Core\Security::e(ucfirst($corr['severity'])) ?>
                                    </span>
                                </td>
                                <td class="py-2.5 text-gray-500 max-w-sm">
                                    <div class="truncate" title="<?= \App\Core\Security::e($corr['explanation']) ?>">
                                        <?= \App\Core\Security::e(substr($corr['explanation'], 0, 80)) ?>…
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center h-24 text-center">
                <p class="text-gray-500 text-sm">No correlations found yet.</p>
                <p class="text-gray-600 text-xs mt-1">Run the Intelligence Worker to generate threat correlations.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================ -->
    <!-- INGESTION + MAP (v0.3.0)                                     -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <!-- Zambia Cyber Map Placeholder -->
        <div class="lg:col-span-2 bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Zambia Cyber Map
                </h2>
                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">Leaflet.js Ready</span>
            </div>
            <div id="zambia-map" class="w-full h-80 bg-gray-800 rounded border border-gray-700 flex items-center justify-center relative overflow-hidden">
                <div class="text-center z-10">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7"/></svg>
                    <p class="text-gray-500 text-sm">Zambia Cyber Situational Map</p>
                    <p class="text-gray-600 text-xs mt-1">Leaflet.js initialized - awaiting geo data</p>
                </div>
                <div class="absolute inset-0 opacity-10" style="background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px;"></div>
            </div>
        </div>

        <!-- Last Ingestion Status Panel (v0.3.0) -->
        <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-white font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Feed Ingestion Status
                </h2>
                <span class="text-xs bg-gray-700 text-gray-300 px-2 py-1 rounded">v0.3.0</span>
            </div>
            <div class="space-y-3">
                <?php if (empty($ingestionStatus)): ?>
                    <div class="text-gray-500 text-sm text-center py-4">No ingestion runs recorded yet.</div>
                <?php else: ?>
                    <?php foreach ($ingestionStatus as $status): ?>
                        <?php
                            $statusColor = ($status['status'] === 'success') ? 'aegisz-success' : (($status['status'] === 'failed') ? 'aegisz-danger' : 'aegisz-warning');
                            $lastRun = $status['last_run_at'] ? date('Y-m-d H:i', strtotime($status['last_run_at'])) : 'Never';
                        ?>
                        <div class="flex items-center justify-between p-3 bg-gray-800/50 rounded border border-gray-700/50">
                            <div>
                                <div class="text-sm text-white font-medium"><?= \App\Core\Security::e(str_replace('Worker', '', $status['worker_name'])) ?></div>
                                <div class="text-xs text-gray-500 mt-0.5">Last: <?= \App\Core\Security::e($lastRun) ?></div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-xs font-medium bg-<?= $statusColor ?>-900/30 text-<?= $statusColor ?>-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <?= \App\Core\Security::e(ucfirst($status['status'])) ?>
                                </span>
                                <?php if ($status['records_fetched'] > 0): ?>
                                    <div class="text-xs text-gray-500 mt-1"><?= $status['records_inserted'] ?> in / <?= $status['records_fetched'] ?> fetched</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-white font-semibold flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Recent Activity
            </h2>
            <a href="<?= \App\Core\Security::e($baseUrl ?? '/aegisz-sentinel') ?>/logs" class="text-xs text-aegisz-accent hover:underline">View All</a>
        </div>
        <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
            <?php if (empty($recentActivity)): ?>
                <div class="text-gray-500 text-sm text-center py-8">No activity recorded yet.</div>
            <?php else: ?>
                <?php foreach ($recentActivity as $log): ?>
                    <?php
                        $level = $log['level'] ?? 'info';
                        $levelColor = ($level === 'error') ? 'aegisz-danger' : (($level === 'warning') ? 'aegisz-warning' : 'aegisz-success');
                    ?>
                    <div class="flex items-start gap-3 p-3 bg-gray-800/50 rounded border border-gray-700/50">
                        <div class="w-2 h-2 rounded-full mt-1.5 bg-<?= $levelColor ?> shrink-0"></div>
                        <div class="min-w-0">
                            <div class="text-sm text-gray-300 truncate"><?= \App\Core\Security::e($log['message'] ?? 'Unknown event') ?></div>
                            <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                <span><?= \App\Core\Security::e($log['source'] ?? 'system') ?></span>
                                <span>&middot;</span>
                                <span><?= \App\Core\Security::e($log['created_at'] ?? 'N/A') ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Info -->
    <div class="mt-6 border-t border-gray-700 pt-4 text-xs text-gray-500 flex justify-between items-center">
        <div>AegisZ Sentinel v<?= \App\Core\Security::e($version) ?> &mdash; Intelligence Platform</div>
        <div>Server: <?= \App\Core\Security::e($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') ?> | PHP <?= phpversion() ?></div>
    </div>
</div>
