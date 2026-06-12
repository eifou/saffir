<?php
// metroflow_subscriptions_offers.php - Pricing plans and offers
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';

$user = getCurrentUser();
?>

<div class="container animate-slide-in">
    <div style="text-align: center; margin-bottom: 56px;">
        <h1 style="margin-bottom: 16px;">Flexible Transit Subscriptions</h1>
        <p style="font-size: 1.1rem; color: var(--grey-600); max-width: 700px; margin: 0 auto;">
            Save money on your daily commutes in Annaba. Choose a subscription tier that matches your travel frequency and ride the metropolitan network cash-free.
        </p>
    </div>

    <!-- Pricing Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; margin-bottom: 64px; align-items: stretch;">
        
        <!-- Pass 1 -->
        <div class="card" style="display: flex; flex-direction: column; position: relative; border-top: 6px solid var(--secondary);">
            <span class="badge badge-success" style="position: absolute; top: 16px; right: 16px;">Student Special</span>
            
            <h3 style="font-size: 1.5rem; margin-bottom: 8px;">Monthly Student Pass</h3>
            <p style="font-size: 0.85rem; color: var(--grey-600); margin-bottom: 24px;">Ideal for Badji Mokhtar University students</p>
            
            <div style="margin-bottom: 32px;">
                <span style="font-size: 2.5rem; font-weight: 800; color: var(--primary);">1,500 DA</span>
                <span style="color: var(--grey-600);">/ month</span>
            </div>
            
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px; margin-bottom: 40px; font-size: 0.95rem;">
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Unlimited rides on all University Lines</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Valid on Bus & Tram routes</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Integrated digital barcode scanner ticket</li>
                <li><i class="fa-solid fa-xmark" style="color: var(--error); margin-right: 8px;"></i> Expired weekend routes excluded</li>
            </ul>
            
            <div style="margin-top: auto;">
                <?php if ($user && $user['role'] === 'passenger'): ?>
                    <a href="saffir_interactive_dashboard.php?action=buy&plan=Monthly+Student+Pass&price=1500" class="btn btn-secondary" style="width: 100%;">
                        <i class="fa-solid fa-cart-shopping"></i> Purchase Plan
                    </a>
                <?php elseif ($user): ?>
                    <button class="btn btn-light" style="width: 100%; cursor: not-allowed;" disabled>
                        Passenger Account Required
                    </button>
                <?php else: ?>
                    <a href="metroflow_join_us.php" class="btn btn-primary" style="width: 100%;">
                        Sign In to Purchase
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pass 2 (Featured) -->
        <div class="card" style="display: flex; flex-direction: column; position: relative; border: 3px solid var(--primary); transform: scale(1.03); box-shadow: var(--shadow-lg);">
            <div style="background-color: var(--primary); color: var(--white); text-align: center; padding: 6px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; border-top-left-radius: 6px; border-top-right-radius: 6px; margin: -25px -25px 20px -25px;">
                Most Popular
            </div>
            
            <h3 style="font-size: 1.5rem; margin-bottom: 8px;">Daily Commuter</h3>
            <p style="font-size: 0.85rem; color: var(--grey-600); margin-bottom: 24px;">Perfect for occasional workers or tourists</p>
            
            <div style="margin-bottom: 32px;">
                <span style="font-size: 2.5rem; font-weight: 800; color: var(--primary);">120 DA</span>
                <span style="color: var(--grey-600);">/ 24 hours</span>
            </div>
            
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px; margin-bottom: 40px; font-size: 0.95rem;">
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Unlimited rides on all routes</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Access to Bus, Tram, and Metro lines</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> 24 hour network-wide validity</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Shareable companion scan code</li>
            </ul>
            
            <div style="margin-top: auto;">
                <?php if ($user && $user['role'] === 'passenger'): ?>
                    <a href="saffir_interactive_dashboard.php?action=buy&plan=Daily+Commuter&price=120" class="btn btn-primary" style="width: 100%;">
                        <i class="fa-solid fa-cart-shopping"></i> Purchase Plan
                    </a>
                <?php elseif ($user): ?>
                    <button class="btn btn-light" style="width: 100%; cursor: not-allowed;" disabled>
                        Passenger Account Required
                    </button>
                <?php else: ?>
                    <a href="metroflow_join_us.php" class="btn btn-primary" style="width: 100%;">
                        Sign In to Purchase
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Pass 3 -->
        <div class="card" style="display: flex; flex-direction: column; position: relative; border-top: 6px solid var(--primary-container);">
            <h3 style="font-size: 1.5rem; margin-bottom: 8px;">Monthly Network Pass</h3>
            <p style="font-size: 0.85rem; color: var(--grey-600); margin-bottom: 24px;">Designed for dedicated daily commuters</p>
            
            <div style="margin-bottom: 32px;">
                <span style="font-size: 2.5rem; font-weight: 800; color: var(--primary);">3,500 DA</span>
                <span style="color: var(--grey-600);">/ month</span>
            </div>
            
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 16px; margin-bottom: 40px; font-size: 0.95rem;">
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Unlimited rides on all network lines</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Free transfers between bus and tramways</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Priority notifications on line status</li>
                <li><i class="fa-solid fa-check" style="color: var(--secondary); margin-right: 8px;"></i> 10% discount on family group addons</li>
            </ul>
            
            <div style="margin-top: auto;">
                <?php if ($user && $user['role'] === 'passenger'): ?>
                    <a href="saffir_interactive_dashboard.php?action=buy&plan=Monthly+Network+Pass&price=3500" class="btn btn-secondary" style="width: 100%;">
                        <i class="fa-solid fa-cart-shopping"></i> Purchase Plan
                    </a>
                <?php elseif ($user): ?>
                    <button class="btn btn-light" style="width: 100%; cursor: not-allowed;" disabled>
                        Passenger Account Required
                    </button>
                <?php else: ?>
                    <a href="metroflow_join_us.php" class="btn btn-primary" style="width: 100%;">
                        Sign In to Purchase
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
    </div>

    <!-- Security Information -->
    <div class="card glass-card" style="padding: 32px; text-align: center; max-width: 800px; margin: 0 auto 40px auto;">
        <h3 style="margin-bottom: 16px; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fa-solid fa-shield-halved" style="color: var(--secondary);"></i>
            Secure Payments & Wallet Top-up
        </h3>
        <p style="font-size: 0.95rem; color: var(--grey-800); max-width: 600px; margin: 0 auto 20px auto;">
            SAFFIR uses a secure internal digital wallet. Top up your account balance anytime from your passenger portal and purchase passes with a single click. Security is fully compliant with local network safety guidelines.
        </p>
        <div style="display: flex; justify-content: center; gap: 24px; font-size: 1.5rem; color: var(--grey-300);">
            <i class="fa-brands fa-cc-visa"></i>
            <i class="fa-brands fa-cc-mastercard"></i>
            <i class="fa-solid fa-money-bill-transfer"></i>
            <span style="font-size: 0.95rem; font-weight: 700; color: var(--primary); display: inline-flex; align-items: center; border: 1px solid var(--grey-300); padding: 2px 6px; border-radius: 4px;">CIB / SATIM</span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
