<?php
// auth.php - Session management, login, logout, and role guards

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if a user is logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current logged-in user details.
 */
function getCurrentUser() {
    global $db;
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $db->prepare("SELECT id, role, email, name, wallet_balance FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Enforce a specific role or redirect to the login or appropriate dashboard.
 */
function requireRole($role) {
    if (!isLoggedIn()) {
        header("Location: metroflow_join_us.php");
        exit;
    }
    
    $user = getCurrentUser();
    if (!$user || $user['role'] !== $role) {
        // Redirect to their default dashboard if logged in but wrong role
        if ($user) {
            redirectToDashboard($user['role']);
        } else {
            header("Location: metroflow_join_us.php");
        }
        exit;
    }
}

/**
 * Helper to redirect user to their role-specific dashboard.
 */
function redirectToDashboard($role) {
    switch ($role) {
        case 'admin':
            header("Location: saffir_admin_dashboard.php");
            break;
        case 'driver':
            header("Location: saffir_driver_home.php");
            break;
        case 'passenger':
            header("Location: saffir_interactive_dashboard.php");
            break;
        default:
            header("Location: saffir_home_animated.php");
            break;
    }
    exit;
}

/**
 * Process login attempt. Returns error message or null on success.
 */
function handleLogin($email, $password) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            return null; // Success
        }
        return "Invalid email or password.";
    } catch (PDOException $e) {
        return "System error: " . $e->getMessage();
    }
}

/**
 * Process registration attempt. Returns error message or null on success.
 */
function handleRegister($name, $email, $password, $role) {
    global $db;
    
    if (!in_array($role, ['admin', 'driver', 'passenger'])) {
        return "Invalid account type selected.";
    }
    
    try {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            return "An account with that email already exists.";
        }
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Default wallet balance for new passengers, e.g., 20.00 DA/USD
        $initialBalance = ($role === 'passenger') ? 20.0 : 0.0;
        
        $insert = $db->prepare("INSERT INTO users (name, email, password_hash, role, wallet_balance) VALUES (:name, :email, :password_hash, :role, :wallet_balance)");
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'wallet_balance' => $initialBalance
        ]);
        
        // Log user in automatically after registration
        $_SESSION['user_id'] = $db->lastInsertId();
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = $name;
        
        return null; // Success
    } catch (PDOException $e) {
        return "System error: " . $e->getMessage();
    }
}

/**
 * Log out user.
 */
function handleLogout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: saffir_home_animated.php");
    exit;
}
