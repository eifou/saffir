<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed.', 405);

$user = requireAuth();

db()->prepare("DELETE FROM personal_access_tokens WHERE token_id = ?")
   ->execute([$user['token_id']]);

json(['message' => 'Logged out successfully.']);
