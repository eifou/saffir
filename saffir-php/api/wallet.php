<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

$user = requireAuth();

$stmt = db()->prepare("SELECT * FROM passengers WHERE user_id = ? LIMIT 1");
$stmt->execute([$user['user_id']]);
$passenger = $stmt->fetch();
if (!$passenger) error('Only passengers have a wallet.', 403);

$method = $_SERVER['REQUEST_METHOD'];

// ── GET /api/wallet.php ───────────────────────────────────────────────────
if ($method === 'GET') {
    $stmt = db()->prepare("
        SELECT tx_id, amount, tx_date, tx_type
        FROM transactions
        WHERE passenger_id = ?
        ORDER BY tx_date DESC
        LIMIT 20
    ");
    $stmt->execute([$passenger['passenger_id']]);

    json([
        'wallet_balance' => (float) $passenger['wallet_balance'],
        'transactions'   => $stmt->fetchAll(),
    ]);
}

// ── POST /api/wallet.php  { amount } ─────────────────────────────────────
if ($method === 'POST') {
    $errors = validate(['amount' => 'required']);
    if ($errors) error('Validation failed.', 422, $errors);

    $amount = (float) input('amount');
    if ($amount < 1 || $amount > 50000) {
        error('Amount must be between 1 and 50,000 DZD.', 422);
    }

    db()->prepare("UPDATE passengers SET wallet_balance = wallet_balance + ? WHERE passenger_id = ?")
       ->execute([$amount, $passenger['passenger_id']]);

    db()->prepare("
        INSERT INTO transactions (tx_id, passenger_id, amount, tx_date, tx_type)
        VALUES (?, ?, ?, NOW(), 'TOPUP')
    ")->execute([uuid(), $passenger['passenger_id'], $amount]);

    $stmt = db()->prepare("SELECT wallet_balance FROM passengers WHERE passenger_id = ?");
    $stmt->execute([$passenger['passenger_id']]);

    json([
        'message'        => 'Wallet topped up successfully.',
        'wallet_balance' => (float) $stmt->fetchColumn(),
    ]);
}

error('Method not allowed.', 405);
