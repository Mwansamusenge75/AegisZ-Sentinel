<?php
/**
 * AegisZ Sentinel - Routes (v0.7.0)
 */

use App\Core\Router;

$router = new Router();

// ============================================================
// PUBLIC ROUTES
// ============================================================
$router->get('/login',   'AuthController@loginForm');
$router->post('/login',  'AuthController@loginSubmit');
$router->post('/logout', 'AuthController@logout');

// ============================================================
// NCSAM Operational Map (v0.7.0)
// ============================================================
$router->get('/operations/map', 'OperationsController@map');

// ============================================================
// MAP API ENDPOINTS (v0.7.0) — JSON only, auth-enforced
// ============================================================
$router->get('/api/map/assets',         'Api\MapApiController@assets');
$router->get('/api/map/assets/detail',  'Api\MapApiController@assetDetail');
$router->get('/api/map/incidents',      'Api\MapApiController@incidents');
$router->get('/api/map/alerts',         'Api\MapApiController@alerts');
$router->get('/api/map/threats',        'Api\MapApiController@threats');
$router->get('/api/map/heatmap',        'Api\MapApiController@heatmap');
$router->get('/api/map/province',       'Api\MapApiController@province');
$router->get('/api/map/overview',       'Api\MapApiController@overview');

// ============================================================
// AI INTELLIGENCE API (v0.7.0) — advisory only, auth-enforced
// ============================================================
$router->get('/api/ai/assessment',      'Api\AIApiController@assessment');
$router->get('/api/ai/explain',         'Api\AIApiController@explain');
$router->post('/api/ai/refresh',        'Api\AIApiController@refresh');

// ============================================================
// ASSET LOCATION SET (v0.7.0)
// ============================================================
$router->post('/assets/set-location', 'AssetController@setLocation');

// ============================================================
// PROTECTED WEB ROUTES
// ============================================================
$router->get('/', 'DashboardController@index');

// Alert Workflow (v0.5.0)
$router->get('/alerts',              'AlertWorkflowController@index');
$router->post('/alerts/transition',  'AlertWorkflowController@transition');

// Incident Workflow (v0.5.0)
$router->get('/incidents',              'IncidentWorkflowController@index');
$router->get('/incidents/detail',       'IncidentWorkflowController@detail');
$router->post('/incidents/transition',  'IncidentWorkflowController@transition');
$router->post('/incidents/note',        'IncidentWorkflowController@addNote');

// Assets (v0.6.0)
$router->get('/assets',         'AssetController@index');
$router->get('/assets/detail',  'AssetController@detail');
$router->get('/assets/create',  'AssetController@create');
$router->post('/assets/store',  'AssetController@store');
$router->get('/assets/edit',    'AssetController@edit');
$router->post('/assets/update', 'AssetController@update');
$router->post('/assets/delete', 'AssetController@delete');
$router->post('/assets/note',   'AssetController@addNote');

// IOCs (v0.6.0)
$router->get('/iocs',         'IOCController@index');
$router->get('/iocs/detail',  'IOCController@detail');
$router->get('/iocs/create',  'IOCController@create');
$router->post('/iocs/store',  'IOCController@store');
$router->get('/iocs/edit',    'IOCController@edit');
$router->post('/iocs/update', 'IOCController@update');
$router->post('/iocs/flag',   'IOCController@flag');
$router->post('/iocs/delete', 'IOCController@delete');

// Threat Explorer (v0.6.0 read-only)
$router->get('/threats',        'ThreatController@index');
$router->get('/threats/detail', 'ThreatController@detail');

// Correlation Explorer (v0.6.0 read-only)
$router->get('/correlations',        'CorrelationController@index');
$router->get('/correlations/detail', 'CorrelationController@detail');

// System
$router->get('/logs',   'LogController@index');
$router->get('/status', 'StatusController@index');

// Intelligence (v0.4.0)
$router->get('/intelligence', 'IntelligenceController@index');

// ============================================================
// ADMIN ROUTES
// ============================================================
$router->get('/admin/users',           'Admin\UserAdminController@index');
$router->get('/admin/users/create',    'Admin\UserAdminController@create');
$router->post('/admin/users/store',    'Admin\UserAdminController@store');
$router->get('/admin/users/edit',      'Admin\UserAdminController@edit');
$router->post('/admin/users/update',   'Admin\UserAdminController@update');
$router->post('/admin/users/delete',   'Admin\UserAdminController@delete');
$router->post('/admin/users/password', 'Admin\UserAdminController@resetPassword');

// ============================================================
// API ROUTES (v0.2.0 Placeholders)
// ============================================================
$router->get('/api/assets',    'Api\AssetApiController@index');
$router->get('/api/iocs',      'Api\IOCApiController@index');
$router->get('/api/threats',   'Api\ThreatApiController@index');
$router->get('/api/alerts',    'Api\AlertApiController@index');
$router->get('/api/incidents', 'Api\IncidentApiController@index');

// Dispatch
$router->dispatch();
