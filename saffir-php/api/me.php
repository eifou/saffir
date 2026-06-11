<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response = [
        'user_id'  => $user['user_id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => strtolower($user['role']),
    ];

    // If passenger, add wallet balance
    if (strtoupper($user['role']) === 'PASSENGER') {
        $stmt = db()->prepare("SELECT wallet_balance FROM passengers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['user_id']]);
        $p = $stmt->fetch();
        $response['wallet_balance'] = $p ? (float) $p['wallet_balance'] : 0.0;
    }

    json($response);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $allowed = [];
    if (input('username')) $allowed['username'] = trim(input('username'));
    if (input('email'))    $allowed['email']    = strtolower(trim(input('email')));

    if (empty($allowed)) error('Nothing to update.', 400);

    foreach ($allowed as $col => $val) {
        db()->prepare("UPDATE users SET $col = ? WHERE user_id = ?")
           ->execute([$val, $user['user_id']]);
    }

    json(['message' => 'Profile updated.']);
}

error('Method not allowed.', 405);
