<?php
// header.php - Common website header with session-aware navigation
require_once __DIR__ . '/auth.php';
$user = getCurrentUser();

// Detect current page for active nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saffir - Smart Digital Platform for Urban Transport</title>
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header>
    <div class="container header-nav">
        <!-- Logo -->
        <a href="saffir_home_animated.php" class="logo">Saffir</a>

        <!-- Center Nav Links -->
        <nav>
            <ul class="nav-links">
                <li class="<?php echo $currentPage === 'saffir_home_animated.php' ? 'active' : ''; ?>">
                    <a href="saffir_home_animated.php">Plan Trip</a>
                </li>
                <li class="<?php echo $currentPage === 'metroflow_subscriptions_offers.php' ? 'active' : ''; ?>">
                    <a href="metroflow_subscriptions_offers.php">Subscriptions</a>
                </li>
                <li class="<?php echo $currentPage === 'saffir_schedules.php' ? 'active' : ''; ?>">
                    <a href="saffir_schedules.php">Schedules</a>
                </li>

                <?php if ($user): ?>
                    <!-- Role-based dashboard links -->
                    <?php if ($user['role'] === 'admin'): ?>
                        <li class="<?php echo $currentPage === 'saffir_admin_dashboard.php' ? 'active' : ''; ?>">
                            <a href="saffir_admin_dashboard.php"><i class="fa-solid fa-gauge-high"></i> Admin</a>
                        </li>
                    <?php elseif ($user['role'] === 'driver'): ?>
                        <li class="<?php echo $currentPage === 'saffir_driver_home.php' ? 'active' : ''; ?>">
                            <a href="saffir_driver_home.php"><i class="fa-solid fa-road"></i> My Shift</a>
                        </li>
                    <?php elseif ($user['role'] === 'passenger'): ?>
                        <li class="<?php echo $currentPage === 'saffir_interactive_dashboard.php' ? 'active' : ''; ?>">
                            <a href="saffir_interactive_dashboard.php"><i class="fa-solid fa-user-gear"></i> My Portal</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Right: Auth Buttons -->
        <div class="nav-auth">
            <?php if ($user): ?>
                <span class="nav-user">
                    <i class="fa-solid fa-user"></i>
                    <?php echo htmlspecialchars($user['name']); ?>
                    <span class="badge badge-info" style="font-size: 0.7rem; padding: 2px 8px;"><?php echo ucfirst($user['role']); ?></span>
                </span>
                <a href="metroflow_join_us.php?action=logout" class="btn-nav-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Exit
                </a>
            <?php else: ?>
                <a href="metroflow_join_us.php" class="btn-nav-login">Login</a>
                <a href="metroflow_join_us.php" class="btn-nav-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main style="flex: 1; padding: 40px 0;">
