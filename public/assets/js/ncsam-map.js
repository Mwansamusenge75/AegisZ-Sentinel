/**
 * AegisZ Sentinel - NCSAM Operational Map (v0.7.0)
 * Leaflet.js + OpenStreetMap. No Google Maps, no paid services.
 * Loads data from /api/map/* JSON endpoints. Read-only consumption —
 * this script never writes back to the platform except via the existing
 * "set location" form (a normal authenticated POST, not from this file).
 */

(function () {
    'use strict';

    const BASE = window.AEGISZ_BASE_URL || '';
    const SEVERITY_COLOR = { critical: '#ef4444', high: '#f97316', medium: '#eab308', low: '#3b82f6' };

    // ---- Map init ----
    const map = L.map('ncsam-map', { zoomControl: true }).setView([-13.1339, 27.8493], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    // Layer groups
    const assetCluster    = L.markerClusterGroup();
    const incidentCluster = L.markerClusterGroup();
    const alertCluster    = L.markerClusterGroup();
    const threatLayer     = L.layerGroup();
    let   heatLayer       = null;

    map.addLayer(assetCluster);
    map.addLayer(incidentCluster);
    map.addLayer(alertCluster);

    // ---- Province markers (centroid-based; see public/assets/data/zambia-provinces.json) ----
    fetch(`${BASE}/assets/data/zambia-provinces.json`)
        .then(r => r.json())
        .then(geo => {
            geo.provinces.forEach(p => {
                const marker = L.circleMarker([p.lat, p.lng], {
                    radius: 8, color: '#0ea5e9', weight: 2, fillColor: '#0ea5e9', fillOpacity: 0.25,
                }).addTo(map);
                marker.bindTooltip(`${p.name} Province (${p.capital})`, { permanent: false });
                marker.on('click', () => loadProvincePanel(p.name));
            });
        })
        .catch(() => console.warn('Could not load province centroid data'));

    // ---- Status pill ----
    const statusPill = document.getElementById('map-status-pill');
    const scorePill  = document.getElementById('national-score-pill');

    function setStatus(text, color) {
        statusPill.textContent = text;
        statusPill.className = `text-xs px-2 py-0.5 rounded bg-${color}-900/30 text-${color}-400`;
    }

    // ---- Fetch helper ----
    function apiGet(path) {
        return fetch(`${BASE}${path}`, { headers: { 'Accept': 'application/json' } })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(j => j.success ? j.data : Promise.reject(j.error || 'Unknown error'));
    }

    // ---- Asset markers ----
    function criticalityColor(c) {
        return SEVERITY_COLOR[c] || '#6b7280';
    }

    function loadAssets() {
        const params = buildFilterParams();
        apiGet(`/api/map/assets?${params}`).then(assets => {
            assetCluster.clearLayers();
            assets.forEach(a => {
                const marker = L.circleMarker([a.lat, a.lng], {
                    radius: 7,
                    color: a.operational ? '#10b981' : '#6b7280',
                    weight: a.operational ? 2 : 1,
                    fillColor: criticalityColor(a.criticality),
                    fillOpacity: 0.85,
                });
                marker.bindPopup(`<b>${escapeHtml(a.name)}</b><br>${escapeHtml(a.department || '')}<br>Criticality: ${escapeHtml(a.criticality)}`);
                marker.on('click', () => loadAssetPanel(a.id));
                assetCluster.addLayer(marker);
            });
            setStatus(`${assets.length} assets`, 'aegisz-accent');
        }).catch(err => setStatus('Asset load failed', 'aegisz-danger'));
    }

    // ---- Incident markers ----
    function loadIncidents() {
        apiGet('/api/map/incidents').then(incidents => {
            incidentCluster.clearLayers();
            incidents.forEach(i => {
                const marker = L.circleMarker([i.lat, i.lng], {
                    radius: 9,
                    color: i.color,
                    weight: 2,
                    fillColor: i.color,
                    fillOpacity: i.faded ? 0.2 : 0.7,
                    className: i.pulsing ? 'ncsam-pulse' : '',
                });
                marker.bindPopup(`<b>Incident:</b> ${escapeHtml(i.title)}<br>Severity: ${escapeHtml(i.severity)}<br>Status: ${escapeHtml(i.status)}<br>Asset: ${escapeHtml(i.asset_name)}`);
                incidentCluster.addLayer(marker);
            });
        }).catch(() => {});
    }

    // ---- Alert markers ----
    function loadAlerts() {
        apiGet('/api/map/alerts').then(alerts => {
            alertCluster.clearLayers();
            alerts.forEach(a => {
                const marker = L.circleMarker([a.lat, a.lng], {
                    radius: 6, color: a.color, weight: 2, fillColor: a.color, fillOpacity: 0.6,
                });
                marker.bindPopup(`<b>Alert:</b> ${escapeHtml(a.title)}<br>Severity: ${escapeHtml(a.severity)}<br>Source: ${escapeHtml(a.source || '—')}<br>Assigned: ${escapeHtml(a.assigned || 'Unassigned')}<br>${escapeHtml(a.created_at)}`);
                alertCluster.addLayer(marker);
            });
        }).catch(() => {});
    }

    // ---- Heatmap ----
    function loadHeatmap() {
        apiGet('/api/map/heatmap').then(points => {
            if (heatLayer) map.removeLayer(heatLayer);
            heatLayer = L.heatLayer(points, { radius: 35, blur: 25, maxZoom: 12 });
        }).catch(() => {});
    }

    // ---- Threat origins (only renders where real data exists) ----
    function loadThreatOrigins() {
        apiGet('/api/map/threats').then(threats => {
            threatLayer.clearLayers();
            threats.forEach(t => {
                if (!t.origin_lat || !t.origin_lng) return;
                const marker = L.circleMarker([t.origin_lat, t.origin_lng], {
                    radius: 6, color: '#a855f7', weight: 2, fillColor: '#a855f7', fillOpacity: 0.5,
                });
                marker.bindPopup(`<b>${escapeHtml(t.title)}</b><br>Origin: ${escapeHtml(t.origin_country || 'Unknown')}<br>Severity: ${escapeHtml(t.severity)}`);
                threatLayer.addLayer(marker);
            });
            if (threats.length === 0) {
                console.info('[NCSAM] No threats have known geographic origin yet — no fabricated paths are drawn.');
            }
        }).catch(() => {});
    }

    // ---- Detail panel: Asset ----
    const panel = document.getElementById('detail-panel');
    const panelContent = document.getElementById('detail-panel-content');
    document.getElementById('close-panel').addEventListener('click', () => panel.classList.add('hidden'));

    function loadAssetPanel(assetId) {
        apiGet(`/api/map/assets/detail?id=${assetId}`).then(d => {
            panel.classList.remove('hidden');
            panelContent.innerHTML = `
                <h3 class="text-white font-semibold text-lg mb-1">${escapeHtml(d.name)}</h3>
                <p class="text-xs text-gray-500 mb-4">${escapeHtml(d.department || '')}</p>
                <div class="space-y-2 text-sm mb-4">
                    <div class="flex justify-between"><span class="text-gray-500">Criticality</span><span class="text-gray-200">${escapeHtml(d.criticality)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="text-gray-200">${escapeHtml(d.status)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Risk Score</span><span class="text-gray-200">${d.risk_score ?? '—'}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Last Seen</span><span class="text-gray-200 text-xs">${escapeHtml(d.last_seen || '—')}</span></div>
                </div>
                <div class="text-xs text-gray-500 mb-1">Open Alerts (${d.open_alerts.length})</div>
                <div class="space-y-1 mb-3">${d.open_alerts.map(a => `<div class="text-xs text-gray-300 bg-gray-800/50 rounded px-2 py-1">${escapeHtml(a.title)}</div>`).join('') || '<div class="text-xs text-gray-600">None</div>'}</div>
                <div class="text-xs text-gray-500 mb-1">Open Incidents (${d.open_incidents.length})</div>
                <div class="space-y-1 mb-3">${d.open_incidents.map(i => `<div class="text-xs text-gray-300 bg-gray-800/50 rounded px-2 py-1">${escapeHtml(i.title)}</div>`).join('') || '<div class="text-xs text-gray-600">None</div>'}</div>
                ${d.latest_ioc ? `<div class="text-xs text-gray-500 mb-1">Latest IOC</div><div class="text-xs font-mono text-aegisz-accent bg-gray-800/50 rounded px-2 py-1 mb-3">${escapeHtml(d.latest_ioc.value)} (${d.latest_ioc.confidence}%)</div>` : ''}
                <a href="${BASE}${d.detail_url}" class="block text-center bg-aegisz-accent hover:bg-sky-400 text-white text-xs font-medium px-4 py-2 rounded-lg transition-colors">Open Asset Page</a>
            `;
        }).catch(() => {});
    }

    // ---- Detail panel: Province ----
    function loadProvincePanel(provinceName) {
        apiGet(`/api/map/province?name=${encodeURIComponent(provinceName)}`).then(d => {
            panel.classList.remove('hidden');
            panelContent.innerHTML = `
                <h3 class="text-white font-semibold text-lg mb-3">${escapeHtml(d.province)} Province</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Assets</span><span class="text-gray-200">${d.asset_count}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Critical Assets</span><span class="text-aegisz-danger-400">${d.critical_assets}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Open Alerts</span><span class="text-aegisz-warning-400">${d.open_alerts}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Open Incidents</span><span class="text-aegisz-danger-400">${d.open_incidents}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Province Risk Score</span><span class="text-gray-200 font-mono">${d.province_risk}/100</span></div>
                </div>
                <div class="text-xs text-gray-600 mt-4">Last update: ${escapeHtml(d.last_update)}</div>
            `;
        }).catch(() => {});
    }

    // ---- National Overview panel ----
    function loadOverview() {
        apiGet('/api/map/overview').then(d => {
            document.getElementById('ov-province').textContent  = d.highest_risk_province || '—';
            document.getElementById('ov-alerts').textContent    = d.total_active_alerts;
            document.getElementById('ov-incidents').textContent = d.total_open_incidents;
            document.getElementById('ov-critical').textContent  = d.critical_assets;
            document.getElementById('ov-online').textContent    = d.assets_online;
            document.getElementById('ov-offline').textContent   = d.assets_offline;

            if (d.security_posture_score !== null) {
                scorePill.textContent = `${d.security_posture_score}/100 — ${d.security_posture_rating}`;
            }
        }).catch(() => {});
    }

    // ---- Filters ----
    function buildFilterParams() {
        const crit = document.getElementById('filter-criticality').value;
        const prov = document.getElementById('filter-province').value;
        const params = new URLSearchParams();
        if (crit) params.set('criticality', crit);
        if (prov) params.set('province', prov);
        return params.toString();
    }

    document.getElementById('filter-criticality').addEventListener('change', loadAssets);
    document.getElementById('filter-province').addEventListener('change', loadAssets);

    // ---- Layer toggles ----
    document.getElementById('layer-assets').addEventListener('change', e => {
        e.target.checked ? map.addLayer(assetCluster) : map.removeLayer(assetCluster);
    });
    document.getElementById('layer-incidents').addEventListener('change', e => {
        e.target.checked ? map.addLayer(incidentCluster) : map.removeLayer(incidentCluster);
    });
    document.getElementById('layer-alerts').addEventListener('change', e => {
        e.target.checked ? map.addLayer(alertCluster) : map.removeLayer(alertCluster);
    });
    document.getElementById('layer-heatmap').addEventListener('change', e => {
        if (e.target.checked) {
            loadHeatmap();
        } else if (heatLayer) {
            map.removeLayer(heatLayer);
        }
    });
    document.getElementById('layer-threats').addEventListener('change', e => {
        if (e.target.checked) {
            loadThreatOrigins();
            map.addLayer(threatLayer);
        } else {
            map.removeLayer(threatLayer);
        }
    });

    // ---- Intelligence Bus timeline (read-only display) ----
    function loadBusTimeline() {
        // Reuses the existing audit/intelligence endpoints if available; this
        // is a lightweight visual strip, not a full scrubber control.
        const el = document.getElementById('bus-timeline');
        el.innerHTML = '<span class="text-gray-600">Timeline reflects Intelligence Worker runs — refresh after next scheduled run.</span>';
    }

    // ---- Util ----
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    // ---- Init ----
    setStatus('Loading…', 'gray');
    loadAssets();
    loadIncidents();
    loadAlerts();
    loadOverview();
    loadBusTimeline();
})();
