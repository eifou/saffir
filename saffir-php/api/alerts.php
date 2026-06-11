<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') error('Method not allowed.', 405);

$status = strtoupper($_GET['status'] ?? 'OPEN');
$allowed = ['OPEN', 'RESOLVED'];
if (!in_array($status, $allowed)) $status = 'OPEN';

$stmt = db()->prepare("
    SELECT a.*, v.vehicle_type, v.status AS vehicle_status
    FROM alerts a
    LEFT JOIN vehicles v ON v.vehicle_id = a.vehicle_id
    WHERE a.status = ?
    ORDER BY a.created_at DESC
    LIMIT 20
");
$stmt->execute([$status]);

json($stmt->fetchAll());
