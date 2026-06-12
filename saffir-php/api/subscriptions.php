<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user = requireAuth();

// Get passenger record
$stmt = db()->prepare("SELECT * FROM passengers WHERE user_id = ? LIMIT 1");
$stmt->execute([$user['user_id']]);
$passenger = $stmt->fetch();
if (!$passenger) error('Only passengers can manage subscriptions.', 403);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET /api/subscriptions.php ────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = db()->prepare("
        SELECT * FROM subscriptions
        WHERE passenger_id = ?
        ORDER BY valid_until DESC
    ");
    $stmt->execute([$passenger['passenger_id']]);
    json($stmt->fetchAll());
}

// ── POST /api/subscriptions.php  { type: DAILY|WEEKLY|MONTHLY|YEARLY } ───
if ($method === 'POST') {
    $errors = validate(['type' => 'required|in:DAILY,WEEKLY,MONTHLY,YEARLY']);
    if ($errors) error('Validation failed.', 422, $errors);

    $type = strtoupper(input('type'));

    $prices = [
        'DAILY'   => 2.75,
        'WEEKLY'  => 14.99,
        'MONTHLY' => 49.99,
        'YEARLY'  => 499.99,
    ];

    $durations = [
        'DAILY'   => '+1 day',
        'WEEKLY'  => '+1 week',
        'MONTHLY' => '+1 month',
        'YEARLY'  => '+1 year',
    ];

    $price = $prices[$type];

    if ((float) $passenger['wallet_balance'] < $price) {
        error("Insufficient wallet balance. You need {$price} DZD but have {$passenger['wallet_balance']} DZD. Please top up first.", 422);
    }

    // Deduct from wallet
    db()->prepare("UPDATE passengers SET wallet_balance = wallet_balance - ? WHERE passenger_id = ?")
       ->execute([$price, $passenger['passenger_id']]);

    // Calculate expiry date
    $validUntil = date('Y-m-d', strtotime($durations[$type]));

    // Create subscription
    $subId = uuid();
    db()->prepare("
        INSERT INTO subscriptions (sub_id, passenger_id, type, valid_until, is_active)
        VALUES (?, ?, ?, ?, 1)
    ")->execute([$subId, $passenger['passenger_id'], $type, $validUntil]);

    // Record transaction
    db()->prepare("
        INSERT INTO transactions (tx_id, passenger_id, amount, tx_date, tx_type)
        VALUES (?, ?, ?, NOW(), 'SUBSCRIPTION')
    ")->execute([uuid(), $passenger['passenger_id'], $price]);

    // Get updated balance
    $stmt = db()->prepare("SELECT wallet_balance FROM passengers WHERE passenger_id = ?");
    $stmt->execute([$passenger['passenger_id']]);
    $newBalance = (float) $stmt->fetchColumn();

    json([
        'message'        => 'Subscription activated successfully.',
        'subscription'   => [
            'sub_id'       => $subId,
            'type'         => $type,
            'valid_until'  => $validUntil,
            'is_active'    => 1,
        ],
        'wallet_balance' => $newBalance,
    ], 201);
}

// ── DELETE /api/subscriptions.php?id=xxx ─────────────────────────────────
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) error('Subscription ID is required.', 400);

    $stmt = db()->prepare("
        SELECT * FROM subscriptions WHERE sub_id = ? AND passenger_id = ? LIMIT 1
    ");
    $stmt->execute([$id, $passenger['passenger_id']]);
    $sub = $stmt->fetch();

    if (!$sub) error('Subscription not found.', 404);

    db()->prepare("UPDATE subscriptions SET is_active = 0 WHERE sub_id = ?")
       ->execute([$id]);

    json(['message' => 'Subscription cancelled.']);
}

error('Method not allowed.', 405);
