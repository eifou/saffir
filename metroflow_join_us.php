<?php
// metroflow_join_us.php - User onboarding (login / register) and session control
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';

// Check if user is logging out
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    handleLogout();
}

// Redirect already logged-in users to their dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        redirectToDashboard($user['role']);
    }
}

$errorMsg = null;
$successMsg = null;
$activeForm = 'login'; // 'login' or 'register'

// Process post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_login'])) {
        $activeForm = 'login';
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $errorMsg = "Please fill in all credentials.";
        } else {
            $res = handleLogin($email, $password);
            if ($res === null) {
                // Success - redirect based on user role
                $user = getCurrentUser();
                redirectToDashboard($user['role']);
            } else {
                $errorMsg = $res;
            }
        }
    } elseif (isset($_POST['action_register'])) {
        $activeForm = 'register';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'passenger';
        
        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            $errorMsg = "All registration fields are required.";
        } else {
            $res = handleRegister($name, $email, $password, $role);
            if ($res === null) {
                // Success - redirect based on newly registered role
                redirectToDashboard($role);
            } else {
                $errorMsg = $res;
            }
        }
    }
}

require_once __DIR__ . '/header.php';
?>

<div class="container animate-slide-in" style="max-width: 1000px; margin: 40px auto;">
    
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger" style="margin-bottom: 24px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($errorMsg); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; align-items: start;">
        
        <!-- Interactive Info Panel -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary), var(--primary-container)); color: var(--white); border: none; padding: 40px 32px;">
            <h2 style="color: var(--white); margin-bottom: 20px;">SAFFIR Ecosystem</h2>
            <p style="color: var(--grey-300); margin-bottom: 32px;">Access different workspaces and test the multi-role platform using our mock system roles:</p>
            
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div style="display: flex; gap: 16px; align-items: start;">
                    <div style="width: 48px; height: 48px; background-color: rgba(255, 255, 255, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; color: var(--secondary-container);">
                        <i class="fa-solid fa-gauge"></i>
                    </div>
                    <div>
                        <h4 style="color: var(--white); margin-bottom: 4px;">Agency Administrator</h4>
                        <p style="color: var(--grey-300); font-size: 0.85rem;">Monitors route metrics, fleet statuses, and financial revenues. (Use: <strong>admin@saffir.com</strong> / <strong>admin123</strong>)</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px; align-items: start;">
                    <div style="width: 48px; height: 48px; background-color: rgba(255, 255, 255, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; color: var(--secondary-container);">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <h4 style="color: var(--white); margin-bottom: 4px;">Professional Driver</h4>
                        <p style="color: var(--grey-300); font-size: 0.85rem;">Starts shifts, controls vehicle occupancy, and reports live route status. (Use: <strong>driver@saffir.com</strong> / <strong>driver123</strong>)</p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 16px; align-items: start;">
                    <div style="width: 48px; height: 48px; background-color: rgba(255, 255, 255, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; color: var(--secondary-container);">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h4 style="color: var(--white); margin-bottom: 4px;">Commuting Passenger</h4>
                        <p style="color: var(--grey-300); font-size: 0.85rem;">Top-up wallets, book student subscriptions, and view trip details. (Use: <strong>passenger@saffir.com</strong> / <strong>passenger123</strong>)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Authentication Forms Card -->
        <div class="card" style="padding: 40px 32px;">
            <!-- Toggle Selector -->
            <div style="display: flex; background-color: var(--grey-100); padding: 4px; border-radius: var(--border-radius-md); margin-bottom: 32px;">
                <button type="button" class="btn" id="btnToggleLogin" onclick="showForm('login')" style="flex: 1; min-height: 40px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; background-color: <?php echo $activeForm === 'login' ? 'var(--white)' : 'transparent'; ?>; color: <?php echo $activeForm === 'login' ? 'var(--primary)' : 'var(--grey-600)'; ?>; box-shadow: <?php echo $activeForm === 'login' ? 'var(--shadow-sm)' : 'none'; ?>;">
                    Login Workspace
                </button>
                <button type="button" class="btn" id="btnToggleRegister" onclick="showForm('register')" style="flex: 1; min-height: 40px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; background-color: <?php echo $activeForm === 'register' ? 'var(--white)' : 'transparent'; ?>; color: <?php echo $activeForm === 'register' ? 'var(--primary)' : 'var(--grey-600)'; ?>; box-shadow: <?php echo $activeForm === 'register' ? 'var(--shadow-sm)' : 'none'; ?>;">
                    Create Account
                </button>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" method="POST" action="metroflow_join_us.php" style="display: <?php echo $activeForm === 'login' ? 'block' : 'none'; ?>;">
                <h3 style="margin-bottom: 24px; color: var(--primary);">Welcome Back</h3>
                
                <div class="form-group">
                    <label class="form-label" for="login_email">Email Address</label>
                    <input type="email" class="form-control" id="login_email" name="email" placeholder="example@saffir.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="login_password">Password</label>
                    <input type="password" class="form-control" id="login_password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" name="action_login" class="btn btn-primary" style="width: 100%; margin-top: 12px;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Authenticate
                </button>
            </form>
            
            <!-- Register Form -->
            <form id="registerForm" method="POST" action="metroflow_join_us.php" style="display: <?php echo $activeForm === 'register' ? 'block' : 'none'; ?>;">
                <h3 style="margin-bottom: 24px; color: var(--primary);">Join Transit Network</h3>
                
                <div class="form-group">
                    <label class="form-label" for="reg_name">Full Name</label>
                    <input type="text" class="form-control" id="reg_name" name="name" placeholder="John Doe" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="reg_email">Email Address</label>
                    <input type="email" class="form-control" id="reg_email" name="email" placeholder="john@domain.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="reg_password">Password</label>
                    <input type="password" class="form-control" id="reg_password" name="password" placeholder="Min. 8 characters" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="reg_role">Account Role Type</label>
                    <select class="form-control" id="reg_role" name="role" required>
                        <option value="passenger">Commuting Passenger (Active wallet)</option>
                        <option value="driver">Professional Driver (Route controller)</option>
                        <option value="admin">System Administrator (Global management)</option>
                    </select>
                </div>
                
                <button type="submit" name="action_register" class="btn btn-secondary" style="width: 100%; margin-top: 12px;">
                    <i class="fa-solid fa-user-plus"></i> Initialize Account
                </button>
            </form>
            
        </div>
    </div>
</div>

<script>
function showForm(formType) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginBtn = document.getElementById('btnToggleLogin');
    const registerBtn = document.getElementById('btnToggleRegister');
    
    if (formType === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        
        loginBtn.style.backgroundColor = 'var(--white)';
        loginBtn.style.color = 'var(--primary)';
        loginBtn.style.boxShadow = 'var(--shadow-sm)';
        
        registerBtn.style.backgroundColor = 'transparent';
        registerBtn.style.color = 'var(--grey-600)';
        registerBtn.style.boxShadow = 'none';
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        
        loginBtn.style.backgroundColor = 'transparent';
        loginBtn.style.color = 'var(--grey-600)';
        loginBtn.style.boxShadow = 'none';
        
        registerBtn.style.backgroundColor = 'var(--white)';
        registerBtn.style.color = 'var(--primary)';
        registerBtn.style.boxShadow = 'var(--shadow-sm)';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
