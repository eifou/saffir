<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error('Method not allowed.', 405);

// GET /api/schedules.php?id=xxx  → single schedule
// GET /api/schedules.php?route_id=xxx&date=2026-06-11 → filtered list

if (!empty($_GET['id'])) {
    $stmt = db()->prepare("
        SELECT s.*, r.line_name
        FROM schedules s
        LEFT JOIN routes r ON r.route_id = s.route_id
        WHERE s.schedule_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch();
    if (!$row) error('Schedule not found.', 404);
    json($row);
}

// List with optional filters
$sql    = "SELECT s.*, r.line_name FROM schedules s LEFT JOIN routes r ON r.route_id = s.route_id WHERE 1=1";
$params = [];

if (!empty($_GET['route_id'])) {
    $sql .= " AND s.route_id = ?";
    $params[] = $_GET['route_id'];
}
if (!empty($_GET['date'])) {
    $sql .= " AND DATE(s.departure_time) = ?";
    $params[] = $_GET['date'];
}

$sql .= " ORDER BY s.departure_time ASC LIMIT 50";

$stmt = db()->prepare($sql);
$stmt->execute($params);
json($stmt->fetchAll());
