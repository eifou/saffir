<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('Method not allowed.', 405);

$errors = validate([
    'email'    => 'required|email',
    'password' => 'required',
]);
if ($errors) error('Validation failed.', 422, $errors);

$email    = strtolower(trim(input('email')));
$password = input('password');

// Find user
$stmt = db()->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !verifyPassword($password, $user['password_hash'])) {
    error('Invalid email or password.', 401);
}

// Revoke old tokens (single session)
db()->prepare("DELETE FROM personal_access_tokens WHERE user_id = ?")
   ->execute([$user['user_id']]);

// Issue new token
$rawToken  = generateToken();
$tokenHash = hash('sha256', $rawToken);
$tokenId   = uuid();

db()->prepare("
    INSERT INTO personal_access_tokens (token_id, user_id, token_hash, created_at)
    VALUES (?, ?, ?, NOW())
")->execute([$tokenId, $user['user_id'], $tokenHash]);

$roleDisplay = strtolower($user['role']);

json([
    'message' => 'Logged in successfully.',
    'token'   => $rawToken,
    'user'    => [
        'user_id'  => $user['user_id'],
        'username' => $user['username'],
        'email'    => $user['email'],
        'role'     => $roleDisplay,
    ],
]);
