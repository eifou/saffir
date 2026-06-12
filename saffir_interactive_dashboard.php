<?php
// saffir_interactive_dashboard.php - Passenger Portal
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';

requireRole('passenger');

$user = getCurrentUser();
$errorMsg = null;
$successMsg = null;

// Handle wallet top-up request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_topup'])) {
    $amount = floatval($_POST['amount'] ?? 0.0);
    if ($amount <= 0) {
        $errorMsg = "Top-up amount must be greater than zero.";
    } else {
        try {
            $db->beginTransaction();
            // Update user balance
            $newBalance = $user['wallet_balance'] + $amount;
            $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $user['id']]);
            
            // Insert transaction record
            $stmt = $db->prepare("INSERT INTO transactions (passenger_id, amount, date, type) VALUES (?, ?, ?, 'Wallet Top-up')");
            $stmt->execute([$user['id'], $amount, date('Y-m-d H:i:s')]);
            
            $db->commit();
            $successMsg = "Wallet successfully topped up by " . number_format($amount, 2) . " DA!";
            // Refresh user variable
            $user = getCurrentUser();
        } catch (PDOException $e) {
            $db->rollBack();
            $errorMsg = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Handle subscription purchase request (can come via GET parameters from subscriptions offers page)
if (isset($_GET['action']) && $_GET['action'] === 'buy') {
    $planName = $_GET['plan'] ?? '';
    $price = floatval($_GET['price'] ?? 0.0);
    
    if (!empty($planName) && $price > 0) {
        if ($user['wallet_balance'] < $price) {
            $errorMsg = "Insufficient wallet balance to purchase this pass. Please top-up your wallet.";
        } else {
            try {
                $db->beginTransaction();
                
                // Deduct balance
                $newBalance = $user['wallet_balance'] - $price;
                $stmt = $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
                $stmt->execute([$newBalance, $user['id']]);
                
                // Expiry: 30 days from now
                $validUntil = date('Y-m-d H:i:s', strtotime('+30 days'));
                // Deactivate any existing active subscriptions first
                $stmt = $db->prepare("UPDATE subscriptions SET status = 'Expired' WHERE passenger_id = ?");
                $stmt->execute([$user['id']]);
                
                // Insert new subscription
                $stmt = $db->prepare("INSERT INTO subscriptions (passenger_id, type, valid_until, status) VALUES (?, ?, ?, 'Active')");
                $stmt->execute([$user['id'], $planName, $validUntil]);
                
                // Insert transaction record
                $stmt = $db->prepare("INSERT INTO transactions (passenger_id, amount, date, type) VALUES (?, ?, ?, 'Subscription Purchase')");
                $stmt->execute([$user['id'], -$price, date('Y-m-d H:i:s')]);
                
                $db->commit();
                $successMsg = "Pass '" . htmlspecialchars($planName) . "' successfully purchased and activated!";
                $user = getCurrentUser();
            } catch (PDOException $e) {
                $db->rollBack();
                $errorMsg = "Failed to purchase pass: " . $e->getMessage();
            }
        }
    }
}

// Fetch active subscription for passenger
$activeSub = null;
try {
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE passenger_id = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$user['id']]);
    $activeSub = $stmt->fetch();
} catch (PDOException $e) {}

// Fetch transactions list
$txList = [];
try {
    $stmt = $db->prepare("SELECT * FROM transactions WHERE passenger_id = ? ORDER BY id DESC");
    $stmt->execute([$user['id']]);
    $txList = $stmt->fetchAll();
} catch (PDOException $e) {}

?>

<div class="container animate-slide-in">
    <div style="margin-bottom: 40px;">
        <h1>Passenger Portal</h1>
        <p style="color: var(--grey-600);">Manage transit passes, review your digital wallet funds, and track your travel transactions.</p>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-xmark"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <!-- Portal Grid -->
    <div style="display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 32px; align-items: start; margin-bottom: 48px;">
        
        <!-- Left: Subscription and Journey History -->
        <div style="display: flex; flex-direction: column; gap: 32px;">
            
            <!-- Pass Manager Card (Thesis Figure 7) -->
            <div class="card" style="padding: 28px; border-left: 6px solid var(--secondary);">
                <h3 style="color: var(--primary); margin-bottom: 16px;"><i class="fa-solid fa-id-card"></i> Active Transit Subscription</h3>
                
                <?php if ($activeSub): ?>
                    <div style="background: linear-gradient(135deg, var(--primary), var(--primary-container)); color: var(--white); border-radius: var(--border-radius-md); padding: 24px; position: relative; overflow: hidden; box-shadow: var(--shadow-md);">
                        <div style="position: absolute; right: -20px; bottom: -20px; font-size: 8rem; opacity: 0.05; color: var(--white);">
                            <i class="fa-solid fa-bus"></i>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                            <div>
                                <span class="badge badge-success" style="background-color: var(--secondary-container); color: var(--primary);"><i class="fa-solid fa-circle-check"></i> Active</span>
                                <h4 style="color: var(--white); font-size: 1.35rem; margin-top: 8px;"><?php echo htmlspecialchars($activeSub['type']); ?></h4>
                            </div>
                            <div style="font-size: 1.5rem;"><i class="fa-solid fa-wifi"></i></div>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--grey-300);">Valid until:</div>
                        <div style="font-weight: 600; font-size: 1.05rem; margin-bottom: 16px;"><?php echo date('F d, Y', strtotime($activeSub['valid_until'])); ?></div>
                        
                        <!-- Dynamic barcode mockup -->
                        <div style="background-color: var(--white); padding: 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
                            <div style="display: flex; gap: 2px;">
                                <span style="display:inline-block; width:2px; height:24px; background:var(--primary);"></span>
                                <span style="display:inline-block; width:4px; height:24px; background:var(--primary);"></span>
                                <span style="display:inline-block; width:1px; height:24px; background:var(--primary);"></span>
                                <span style="display:inline-block; width:3px; height:24px; background:var(--primary);"></span>
                                <span style="display:inline-block; width:1px; height:24px; background:var(--primary);"></span>
                                <span style="display:inline-block; width:4px; height:24px; background:var(--primary);"></span>
                                <span style="display:inline-block; width:2px; height:24px; background:var(--primary);"></span>
                            </div>
                            <span style="font-family: monospace; font-size: 0.8rem; font-weight: 700; color: var(--primary);">PASS-<?php echo str_pad($activeSub['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background-color: var(--grey-100); border-radius: var(--border-radius-md); padding: 24px; text-align: center; border: 1px dashed var(--grey-300);">
                        <i class="fa-solid fa-id-card-clip" style="font-size: 2.5rem; color: var(--grey-300); margin-bottom: 12px; display: block;"></i>
                        <p style="color: var(--grey-600); margin-bottom: 16px;">No active ticket or monthly pass currently registered.</p>
                        <a href="metroflow_subscriptions_offers.php" class="btn btn-secondary">
                            <i class="fa-solid fa-cart-shopping"></i> Browse Commuter Passes
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Journey History / Transactions List -->
            <div class="card" style="padding: 28px;">
                <h3 style="color: var(--primary); margin-bottom: 16px;"><i class="fa-solid fa-clock-rotate-left"></i> Transaction History</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction Detail</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($txList)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 24px; color: var(--grey-600);">No transactions logged yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($txList as $tx): 
                                    $isNeg = $tx['amount'] < 0;
                                    $amtColor = $isNeg ? 'var(--error)' : 'var(--secondary)';
                                    $prefix = $isNeg ? '-' : '+';
                                ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($tx['date'])); ?></td>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($tx['type']); ?></td>
                                        <td style="font-weight: 700; color: <?php echo $amtColor; ?>;">
                                            <?php echo $prefix; ?><?php echo number_format(abs($tx['amount']), 2); ?> DA
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
        
        <!-- Right: Digital Wallet Manager -->
        <div style="display: flex; flex-direction: column; gap: 32px;">
            <!-- Wallet balance card -->
            <div class="card" style="padding: 28px; background: linear-gradient(135deg, var(--primary-container), var(--primary)); color: var(--white); border: none;">
                <span style="font-size: 0.85rem; color: var(--grey-300); font-weight: 600; text-transform: uppercase;">Digital Wallet Balance</span>
                <div style="font-size: 2.5rem; font-weight: 800; color: var(--white); margin: 8px 0 24px 0;">
                    <?php echo number_format($user['wallet_balance'], 2); ?> DA
                </div>
                
                <h4 style="color: var(--white); margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 8px;">Top-Up Wallet Funds</h4>
                
                <form method="POST" action="saffir_interactive_dashboard.php">
                    <div class="form-group">
                        <label class="form-label" for="amount" style="color: var(--white);">Select Top-Up Amount (DA)</label>
                        <select class="form-control" name="amount" id="amount" style="background-color: rgba(255,255,255,0.9); border: none; color: var(--primary);" required>
                            <option value="500">500.00 DA</option>
                            <option value="1000" selected>1,000.00 DA</option>
                            <option value="2000">2,000.00 DA</option>
                            <option value="5000">5,000.00 DA</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="action_topup" class="btn btn-secondary" style="width: 100%; border-radius: var(--border-radius-md);">
                        <i class="fa-solid fa-credit-card"></i> Confirm Payment
                    </button>
                </form>
            </div>
            
            <!-- Bookmarked Routes / Live arrival countdowns (Thesis Figure 7) -->
            <div class="card" style="padding: 24px;">
                <h4 style="color: var(--primary); margin-bottom: 16px;"><i class="fa-solid fa-bookmark" style="color: var(--secondary);"></i> Bookmarked Routes</h4>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background-color: var(--grey-100); padding: 12px; border-radius: 8px;">
                        <div>
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--primary);">Line 4 (University)</span>
                            <div style="font-size: 0.75rem; color: var(--grey-600);">Next arrival at Plaine Ouest</div>
                        </div>
                        <span class="badge badge-success">4 mins</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; background-color: var(--grey-100); padding: 12px; border-radius: 8px;">
                        <div>
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--primary);">Line 10 (El Bouni)</span>
                            <div style="font-size: 0.75rem; color: var(--grey-600);">Next arrival at Pont Blanc</div>
                        </div>
                        <span class="badge badge-warning">Delayed (8m)</span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
