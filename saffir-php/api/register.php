<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed.', 405);

// ── Validate ──────────────────────────────────────────────────────────────
$errors = validate([
    'first_name' => 'required',
    'last_name'  => 'required',
    'email'      => 'required|email',
    'password'   => 'required|min:8|has_digit',
    'role'       => 'required|in:passenger,driver,authority',
]);
if ($errors) error('Validation failed.', 422, $errors);

$firstName = trim(input('first_name'));
$lastName  = trim(input('last_name'));
$email     = strtolower(trim(input('email')));
$password  = input('password');
$role      = input('role');

$roleMap = ['passenger' => 'PASSENGER', 'driver' => 'DRIVER', 'authority' => 'ADMIN'];
$dbRole  = $roleMap[$role];

// ── Check duplicate email ─────────────────────────────────────────────────
$stmt = db()->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) error('Validation failed.', 422, ['email' => ['This email is already registered.']]);

// ── Create user ───────────────────────────────────────────────────────────
$userId   = uuid();
$username = strtolower($firstName . '.' . $lastName) . '_' . substr(bin2hex(random_bytes(3)), 0, 5);

db()->prepare("
    INSERT INTO users (user_id, username, password_hash, email, role)
    VALUES (?, ?, ?, ?, ?)
")->execute([$userId, $username, hashPassword($password), $email, $dbRole]);

// ── Create role-specific record ───────────────────────────────────────────
switch ($role) {
    case 'passenger':
        db()->prepare("INSERT INTO passengers (passenger_id, user_id, wallet_balance) VALUES (?, ?, 0.00)")
            ->execute([uuid(), $userId]);
        break;
    case 'driver':
        db()->prepare("INSERT INTO drivers (driver_id, user_id, license_no, rating) VALUES (?, ?, NULL, NULL)")
            ->execute([uuid(), $userId]);
        break;
    case 'authority':
        db()->prepare("INSERT INTO administrators (admin_id, user_id, access_level) VALUES (?, ?, 1)")
            ->execute([uuid(), $userId]);
        break;
}

// ── Issue token ───────────────────────────────────────────────────────────
$rawToken  = generateToken();
$tokenHash = hash('sha256', $rawToken);
$tokenId   = uuid();

db()->prepare("
    INSERT INTO personal_access_tokens (token_id, user_id, token_hash, created_at)
    VALUES (?, ?, ?, NOW())
")->execute([$tokenId, $userId, $tokenHash]);

json([
    'message' => 'Account created successfully.',
    'token'   => $rawToken,
    'user'    => [
        'user_id'  => $userId,
        'username' => $username,
        'email'    => $email,
        'role'     => $role,
    ],
], 201);
