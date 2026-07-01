<?php use App\Core\Security; use App\Middleware\RoleMiddleware; ?>
<div class="max-w-6xl mx-auto">
    <div class="mb-4">
        <a href="<?= Security::e($baseUrl) ?>/assets" class="text-gray-500 hover:text-white text-sm flex items-center gap-1 mb-3"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Assets</a>
        <?php
            $critColor   = ['low'=>'gray','medium'=>'aegisz-warning','high'=>'orange','critical'=>'aegisz-danger'][$asset->criticality] ?? 'gray';
            $statusColor = ['active'=>'aegisz-success','inactive'=>'gray','maintenance'=>'aegisz-warning'][$asset->status] ?? 'gray';
        ?>
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="px-2 py-0.5 rounded text-xs bg-<?= $critColor ?>-900/30 text-<?= $critColor ?>-400"><?= Security::e(ucfirst($asset->criticality)) ?></span>
                    <span class="flex items-center gap-1 text-xs text-<?= $statusColor ?>-400"><span class="w-1.5 h-1.5 rounded-full bg-current"></span><?= Security::e(ucfirst($asset->status)) ?></span>
                </div>
                <h1 class="text-2xl font-bold text-white"><?= Security::e($asset->name) ?></h1>
                <p class="text-gray-500 text-sm mt-1">Added: <?= Security::e($asset->createdAt) ?></p>
            </div>
            <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
                <a href="<?= Security::e($baseUrl) ?>/assets/edit?id=<?= $asset->id ?>" class="shrink-0 bg-gray-700 hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition-colors">Edit Asset</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($flashSuccess)): ?><div class="mb-4 p-3 bg-green-900/30 border border-green-700 rounded-lg text-green-400 text-sm"><?= Security::e($flashSuccess) ?></div><?php endif; ?>
    <?php if (!empty($flashError)): ?><div class="mb-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-400 text-sm"><?= Security::e($flashError) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="space-y-4">
            <!-- Metadata -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-4">Asset Details</h2>
                <div class="space-y-2 text-sm">
                    <?php foreach ([
                        'Type'            => ucfirst($asset->assetType),
                        'IP Address'      => $asset->ipAddress ?? '—',
                        'Hostname'        => $asset->hostname ?? '—',
                        'OS'              => $asset->operatingSystem ?? '—',
                        'Network Segment' => $asset->networkSegment ?? '—',
                        'Department'      => $asset->department ?? '—',
                        'Location'        => $asset->location ?? '—',
                        'Owner'           => $asset->owner ?? '—',
                    ] as $label => $val): ?>
                        <div class="flex justify-between gap-2">
                            <span class="text-gray-500 shrink-0"><?= $label ?></span>
                            <span class="text-gray-200 text-right font-mono text-xs"><?= Security::e($val) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($asset->notes): ?>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <div class="text-xs text-gray-500 mb-1">Notes</div>
                        <p class="text-sm text-gray-300"><?= nl2br(Security::e($asset->notes)) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Set Location on Map (v0.7.0) -->
            <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Set Map Location
                </h2>
                <?php if ($asset->hasGeoLocation()): ?>
                    <p class="text-xs text-aegisz-success-400 mb-2">
                        ✓ Located at <?= number_format($asset->latitude, 5) ?>, <?= number_format($asset->longitude, 5) ?>
                        <?= $asset->province ? '(' . Security::e($asset->province) . ')' : '' ?>
                    </p>
                <?php endif; ?>
                <form method="POST" action="<?= Security::e($baseUrl) ?>/assets/set-location">
                    <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                    <input type="hidden" name="asset_id" value="<?= $asset->id ?>">
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Latitude</label>
                            <input type="number" name="latitude" step="0.00001" value="<?= $asset->latitude ?? '' ?>"
                                   placeholder="-15.3875"
                                   class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1.5 text-white text-xs focus:outline-none focus:border-aegisz-accent">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Longitude</label>
                            <input type="number" name="longitude" step="0.00001" value="<?= $asset->longitude ?? '' ?>"
                                   placeholder="28.3228"
                                   class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1.5 text-white text-xs focus:outline-none focus:border-aegisz-accent">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs text-gray-500 mb-1">Province</label>
                        <select name="province" class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1.5 text-white text-xs focus:outline-none focus:border-aegisz-accent">
                            <option value="">Select province</option>
                            <?php foreach (['Central','Copperbelt','Eastern','Luapula','Lusaka','Muchinga','Northern','North-Western','Southern','Western'] as $p): ?>
                                <option value="<?= $p ?>" <?= $asset->province === $p ? 'selected' : '' ?>><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="district" value="<?= Security::e($asset->district ?? '') ?>" placeholder="District (optional)"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1.5 text-white text-xs focus:outline-none focus:border-aegisz-accent">
                    </div>
                    <div class="mb-3">
                        <input type="text" name="location_name" value="<?= Security::e($asset->locationName ?? '') ?>" placeholder="Location name (optional)"
                               class="w-full bg-gray-800 border border-gray-600 rounded px-2 py-1.5 text-white text-xs focus:outline-none focus:border-aegisz-accent">
                    </div>
                    <button type="submit" class="w-full bg-aegisz-accent hover:bg-sky-400 text-white text-xs font-medium py-2 rounded-lg transition-colors">Pin on Map</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- AI Explanation (v0.7.0) -->
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5" id="ai-panel">
                <h2 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    AI Analysis
                    <span class="text-xs text-purple-400/60">Advisory only</span>
                </h2>
                <div id="ai-content" class="text-xs text-gray-500">
                    <button onclick="loadAIExplanation('asset', <?= (int)$asset->id ?>)"
                            class="w-full text-center bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs py-2 rounded-lg transition-colors border border-gray-700">
                        Explain this asset with AI
                    </button>
                </div>
            </div>

            <!-- Analyst Notes -->
            <?php if (RoleMiddleware::currentUserHasRole('analyst')): ?>
            <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                <h2 class="text-white font-semibold mb-3">Add Note</h2>
                <form method="POST" action="<?= Security::e($baseUrl) ?>/assets/note">
                    <input type="hidden" name="_csrf" value="<?= Security::e($csrfToken) ?>">
                    <input type="hidden" name="asset_id" value="<?= $asset->id ?>">
                    <textarea name="note" rows="3" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-aegisz-accent resize-none" placeholder="Add analyst note…"></textarea>
                    <button type="submit" class="mt-2 bg-aegisz-accent hover:bg-sky-400 text-white text-xs font-medium px-4 py-2 rounded-lg transition-colors">Add Note</button>
                </form>
                <?php if (!empty($notes)): ?>
                    <div class="mt-4 space-y-3">
                        <?php foreach ($notes as $note): ?>
                            <div class="border-l-2 border-gray-600 pl-3">
                                <div class="text-xs text-gray-300"><?= nl2br(Security::e($note['note'])) ?></div>
                                <div class="text-xs text-gray-600 mt-0.5"><?= Security::e($note['username'] ?? '?') ?> · <?= Security::e($note['created_at']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: linked records -->
        <div class="lg:col-span-2 space-y-4">
            <?php
            $sections = [
                ['title' => 'Linked Alerts',       'data' => $alerts,       'key' => 'title', 'color' => 'aegisz-danger',   'link' => false],
                ['title' => 'Linked Incidents',     'data' => $incidents,    'key' => 'title', 'color' => 'aegisz-warning',  'link' => '/incidents/detail'],
                ['title' => 'Linked Correlations',  'data' => $correlations, 'key' => 'explanation', 'color' => 'aegisz-accent', 'link' => '/correlations/detail'],
                ['title' => 'Related Threats',      'data' => $threats,      'key' => 'title', 'color' => 'orange',          'link' => '/threats/detail'],
            ];
            foreach ($sections as $section): ?>
                <div class="bg-aegisz-panel border border-gray-700 rounded-lg p-5">
                    <h2 class="text-white font-semibold mb-3 flex items-center justify-between">
                        <?= $section['title'] ?>
                        <span class="text-xs text-gray-500 font-normal"><?= count($section['data']) ?></span>
                    </h2>
                    <?php if (empty($section['data'])): ?>
                        <p class="text-gray-600 text-sm">None recorded.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($section['data'] as $row): ?>
                                <div class="flex items-start gap-2 p-2 bg-gray-800/40 rounded border-l-2 border-<?= $section['color'] ?>-600">
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm text-gray-200 truncate">
                                            <?php if ($section['link'] && isset($row['id'])): ?>
                                                <a href="<?= Security::e($baseUrl . $section['link']) ?>?id=<?= (int)$row['id'] ?>" class="hover:text-aegisz-accent">
                                                    <?= Security::e(substr($row[$section['key']] ?? '—', 0, 80)) ?>
                                                </a>
                                            <?php else: ?>
                                                <?= Security::e(substr($row[$section['key']] ?? '—', 0, 80)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (isset($row['severity'])): ?>
                                            <span class="text-xs text-gray-500"><?= Security::e(ucfirst($row['severity'])) ?> · <?= Security::e($row['created_at'] ?? '') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>


<script>
function loadAIExplanation(type, id) {
    const el = document.getElementById('ai-content');
    el.innerHTML = '<div class="text-purple-400/70 animate-pulse">Generating AI analysis…</div>';
    fetch(window.location.origin + '<?= Security::e($baseUrl) ?>/api/ai/explain?type=' + type + '&id=' + id)
        .then(r => r.json())
        .then(j => {
            if (!j.success && !j.ai_enabled) {
                el.innerHTML = '<p class="text-gray-600">AI Intelligence Layer not configured.</p>';
                return;
            }
            if (!j.success) {
                el.innerHTML = '<p class="text-red-400 text-xs">' + (j.message || 'Analysis unavailable') + '</p>';
                return;
            }
            const d = j.data;
            el.innerHTML = `
                <div class="space-y-3">
                    <div><div class="text-gray-500 mb-0.5">What happened</div><div class="text-gray-300">${escHtml(d.what_happened)}</div></div>
                    <div><div class="text-gray-500 mb-0.5">Why it matters</div><div class="text-gray-300">${escHtml(d.why_it_matters)}</div></div>
                    <div><div class="text-gray-500 mb-0.5">Potential impact</div><div class="text-gray-300">${escHtml(d.potential_impact)}</div></div>
                    <div><div class="text-gray-500 mb-0.5">Recommended action</div><div class="text-purple-300">${escHtml(d.recommended_action)}</div></div>
                    <div class="text-gray-600 text-xs">AI confidence: ${d.confidence}% — Advisory only.</div>
                </div>`;
        })
        .catch(() => { el.innerHTML = '<p class="text-red-400 text-xs">AI analysis failed.</p>'; });
}
function escHtml(s) {
    const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML;
}
</script>
