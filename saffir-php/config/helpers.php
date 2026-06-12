<?php

/** Send a JSON response and exit */
function json(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Send an error response and exit */
function error(string $message, int $status = 400, array $errors = []): never {
    $body = ['message' => $message];
    if ($errors) $body['errors'] = $errors;
    json($body, $status);
}

/** Get decoded JSON body from request */
function body(): array {
    static $parsed = null;
    if ($parsed === null) {
        $raw    = file_get_contents('php://input');
        $parsed = json_decode($raw, true) ?? [];
    }
    return $parsed;
}

/** Get a value from the request body */
function input(string $key, mixed $default = null): mixed {
    return body()[$key] ?? $default;
}

/** Generate a UUID v4 */
function uuid(): string {
    $data    = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** Generate a random token (used for Bearer tokens) */
function generateToken(): string {
    return bin2hex(random_bytes(40));
}

/** Hash a password */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

/** Verify a password against a hash */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/** Validate required fields; returns array of error messages */
function validate(array $rules): array {
    $body   = body();
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value = $body[$field] ?? null;
        $parts = explode('|', $rule);

        foreach ($parts as $part) {
            if ($part === 'required' && ($value === null || $value === '')) {
                $errors[$field][] = "$field is required.";
            }
            if (str_starts_with($part, 'min:')) {
                $min = (int) substr($part, 4);
                if (is_string($value) && strlen($value) < $min)
                    $errors[$field][] = "$field must be at least $min characters.";
            }
            if ($part === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field][] = "$field must be a valid email address.";
            }
            if (str_starts_with($part, 'in:')) {
                $allowed = explode(',', substr($part, 3));
                if ($value && !in_array($value, $allowed))
                    $errors[$field][] = "$field must be one of: " . implode(', ', $allowed) . ".";
            }
            if ($part === 'has_digit' && $value && !preg_match('/[0-9]/', $value)) {
                $errors[$field][] = "$field must contain at least one number.";
            }
        }
    }

    return $errors;
}

/** Get authenticated user from Bearer token; exits with 401 if invalid */
function requireAuth(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($header, 'Bearer ')) {
        error('Unauthenticated. Please log in.', 401);
    }
    $token = substr($header, 7);

    $stmt = db()->prepare("
        SELECT u.user_id, u.username, u.email, u.role, t.token_id
        FROM personal_access_tokens t
        JOIN users u ON u.user_id = t.user_id
        WHERE t.token_hash = ?
        LIMIT 1
    ");
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();

    if (!$user) error('Invalid or expired token.', 401);

    // Update last_used_at
    db()->prepare("UPDATE personal_access_tokens SET last_used_at = NOW() WHERE token_id = ?")
       ->execute([$user['token_id']]);

    return $user;
}
