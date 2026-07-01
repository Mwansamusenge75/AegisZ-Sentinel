/**
 * AegisZ Sentinel - Frontend Scripts
 * Minimal vanilla JS for UI interactions.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Leaflet map placeholder
    initMapPlaceholder();

    // Auto-refresh timestamps
    updateTimestamps();
    setInterval(updateTimestamps, 60000);

    // Console branding
    console.log('%c AegisZ Sentinel ', 'background: #0ea5e9; color: #fff; font-size: 20px; font-weight: bold; border-radius: 4px;');
    console.log('%c Zambia Cyber Situational Awareness & Threat Intelligence Platform ', 'color: #94a3b8;');
    console.log('%c Foundation v0.1.0 - Not for production use without security hardening ', 'color: #ef4444; font-weight: bold;');
});

/**
 * Initialize the Zambia map placeholder.
 * In production, this will be replaced with Leaflet.js initialization.
 */
function initMapPlaceholder() {
    const mapContainer = document.getElementById('zambia-map');
    if (!mapContainer) return;

    // Add click-to-activate hint
    mapContainer.addEventListener('click', function() {
        console.log('[AegisZ] Map module activated - awaiting geo data integration');
    });

    // Simulate map loading state
    setTimeout(() => {
        const hint = mapContainer.querySelector('.text-gray-600');
        if (hint) {
            hint.textContent = 'Leaflet.js ready - Click to initialize map module';
            hint.classList.remove('text-gray-600');
            hint.classList.add('text-aegisz-accent', 'cursor-pointer');
        }
    }, 2000);
}

/**
 * Update relative timestamps on the page.
 */
function updateTimestamps() {
    const timestamps = document.querySelectorAll('[data-timestamp]');
    timestamps.forEach(el => {
        const ts = el.getAttribute('data-timestamp');
        if (ts) {
            el.textContent = formatRelativeTime(new Date(ts));
        }
    });
}

/**
 * Format a date as relative time.
 */
function formatRelativeTime(date) {
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}
