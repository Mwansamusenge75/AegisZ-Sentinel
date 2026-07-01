<?php use App\Core\Security; ?>
<div class="-m-6 h-[calc(100vh-65px)] flex flex-col">

    <!-- Map toolbar -->
    <div class="bg-aegisz-panel border-b border-gray-700 px-4 py-3 flex items-center justify-between flex-wrap gap-3 z-20">
        <div class="flex items-center gap-3">
            <h1 class="text-white font-bold flex items-center gap-2">
                <svg class="w-5 h-5 text-aegisz-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7m0 0L9 4"/></svg>
                National Cyber Situational Awareness Map
            </h1>
            <span id="map-status-pill" class="text-xs px-2 py-0.5 rounded bg-gray-700 text-gray-400">Loading…</span>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="text-gray-500">National Posture:</span>
            <span id="national-score-pill" class="px-2 py-0.5 rounded bg-gray-700 text-gray-300 font-mono">—</span>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">

        <!-- Left: Layer controls + filters -->
        <div class="w-64 bg-aegisz-panel border-r border-gray-700 overflow-y-auto p-4 space-y-5 z-10">

            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Intelligence Layers</h2>
                <div class="space-y-1.5 text-sm">
                    <?php foreach ([
                        ['id'=>'layer-assets','label'=>'Assets','default'=>true],
                        ['id'=>'layer-incidents','label'=>'Incidents','default'=>true],
                        ['id'=>'layer-alerts','label'=>'Active Alerts','default'=>true],
                        ['id'=>'layer-heatmap','label'=>'Threat Density (Heatmap)','default'=>false],
                        ['id'=>'layer-threats','label'=>'Threat Origins','default'=>false],
                    ] as $l): ?>
                        <label class="flex items-center gap-2 text-gray-300 cursor-pointer hover:text-white">
                            <input type="checkbox" id="<?= $l['id'] ?>" <?= $l['default']?'checked':'' ?> class="layer-toggle rounded bg-gray-800 border-gray-600 text-aegisz-accent focus:ring-aegisz-accent">
                            <?= $l['label'] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Operational Sectors</h2>
                <div class="space-y-1.5 text-sm max-h-48 overflow-y-auto">
                    <?php foreach ([
                        'government'=>'Government','banking'=>'Banking','telecommunications'=>'Telecommunications',
                        'energy'=>'Energy','healthcare'=>'Healthcare','education'=>'Education',
                        'water_utilities'=>'Water Utilities','airports'=>'Airports','border_posts'=>'Border Posts',
                        'internet_exchange'=>'Internet Exchange Points','data_centres'=>'Data Centres',
                        'critical_infrastructure'=>'Critical Infrastructure',
                    ] as $val=>$label): ?>
                        <label class="flex items-center gap-2 text-gray-300 cursor-pointer hover:text-white">
                            <input type="checkbox" class="sector-toggle rounded bg-gray-800 border-gray-600 text-aegisz-accent focus:ring-aegisz-accent" value="<?= $val ?>" checked>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Filters</h2>
                <select id="filter-criticality" class="w-full bg-gray-800 border border-gray-700 text-gray-300 text-xs rounded-lg px-2 py-1.5 mb-2 focus:outline-none focus:border-aegisz-accent">
                    <option value="">All Criticalities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select id="filter-province" class="w-full bg-gray-800 border border-gray-700 text-gray-300 text-xs rounded-lg px-2 py-1.5 focus:outline-none focus:border-aegisz-accent">
                    <option value="">All Provinces</option>
                    <option value="Central">Central</option>
                    <option value="Copperbelt">Copperbelt</option>
                    <option value="Eastern">Eastern</option>
                    <option value="Luapula">Luapula</option>
                    <option value="Lusaka">Lusaka</option>
                    <option value="Muchinga">Muchinga</option>
                    <option value="Northern">Northern</option>
                    <option value="North-Western">North-Western</option>
                    <option value="Southern">Southern</option>
                    <option value="Western">Western</option>
                </select>
            </div>

            <!-- National Overview -->
            <div>
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">National Overview</h2>
                <div id="national-overview" class="space-y-1.5 text-xs text-gray-400">
                    <div class="flex justify-between"><span>Highest Risk Province</span><span id="ov-province" class="text-gray-200">—</span></div>
                    <div class="flex justify-between"><span>Active Alerts</span><span id="ov-alerts" class="text-gray-200">—</span></div>
                    <div class="flex justify-between"><span>Open Incidents</span><span id="ov-incidents" class="text-gray-200">—</span></div>
                    <div class="flex justify-between"><span>Critical Assets</span><span id="ov-critical" class="text-gray-200">—</span></div>
                    <div class="flex justify-between"><span>Assets Online</span><span id="ov-online" class="text-aegisz-success-400">—</span></div>
                    <div class="flex justify-between"><span>Assets Offline</span><span id="ov-offline" class="text-gray-500">—</span></div>
                </div>
            </div>
        </div>

        <!-- Center: Map -->
        <div class="flex-1 relative">
            <div id="ncsam-map" class="absolute inset-0"></div>
        </div>

        <!-- Right: Selection / Province panel -->
        <div id="detail-panel" class="w-80 bg-aegisz-panel border-l border-gray-700 overflow-y-auto p-4 hidden">
            <button id="close-panel" class="text-xs text-gray-500 hover:text-white mb-3">✕ Close</button>
            <div id="detail-panel-content"></div>
        </div>
    </div>

    <!-- Timeline scrubber -->
    <div class="bg-aegisz-panel border-t border-gray-700 px-4 py-2 text-xs text-gray-500 flex items-center gap-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Recent Intelligence Bus Events:</span>
        <div id="bus-timeline" class="flex-1 flex items-center gap-2 overflow-x-auto"></div>
    </div>
</div>

<!-- Leaflet (OpenStreetMap) — no Google Maps, no paid services -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.heat/0.2.0/leaflet-heat.js"></script>

<script>
window.AEGISZ_BASE_URL = <?= json_encode($baseUrl) ?>;
</script>
<script src="<?= Security::e($baseUrl) ?>/assets/js/ncsam-map.js"></script>
