<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error('Method not allowed.', 405);

// Single route with stops
if (!empty($_GET['id'])) {
    $stmt = db()->prepare("SELECT * FROM routes WHERE route_id = ? LIMIT 1");
    $stmt->execute([$_GET['id']]);
    $route = $stmt->fetch();
    if (!$route) error('Route not found.', 404);

    // Get stops in order
    $stmt = db()->prepare("
        SELECT st.*, rs.stop_order
        FROM route_stops rs
        JOIN stops st ON st.stop_id = rs.stop_id
        WHERE rs.route_id = ?
        ORDER BY rs.stop_order ASC
    ");
    $stmt->execute([$_GET['id']]);
    $route['stops'] = $stmt->fetchAll();

    // Get schedules for this route
    $stmt = db()->prepare("
        SELECT * FROM schedules WHERE route_id = ? ORDER BY departure_time ASC LIMIT 20
    ");
    $stmt->execute([$_GET['id']]);
    $route['schedules'] = $stmt->fetchAll();

    json($route);
}

// All routes with their stops
$routes = db()->query("SELECT * FROM routes ORDER BY line_name ASC")->fetchAll();

foreach ($routes as &$route) {
    $stmt = db()->prepare("
        SELECT st.*, rs.stop_order
        FROM route_stops rs
        JOIN stops st ON st.stop_id = rs.stop_id
        WHERE rs.route_id = ?
        ORDER BY rs.stop_order ASC
    ");
    $stmt->execute([$route['route_id']]);
    $route['stops'] = $stmt->fetchAll();
}

json($routes);
