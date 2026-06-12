<?php
// saffir_driver_home.php - Driver shift console and occupancy management
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';

requireRole('driver');

$user = getCurrentUser();
$errorMsg = null;
$successMsg = null;

// Fetch current active shift for this driver
$activeShift = null;
try {
    $stmt = $db->prepare("
        SELECT s.*, v.type as vehicle_type, v.capacity, r.line_name 
        FROM shifts s
        JOIN vehicles v ON s.vehicle_id = v.id
        JOIN routes r ON s.route_id = r.id
        WHERE s.driver_id = :driver_id AND s.status = 'Active'
        LIMIT 1
    ");
    $stmt->execute(['driver_id' => $user['id']]);
    $activeShift = $stmt->fetch();
} catch (PDOException $e) {}

// Handle shift start
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_start_shift'])) {
    $routeId = intval($_POST['route_id'] ?? 0);
    $vehicleId = intval($_POST['vehicle_id'] ?? 0);
    
    if ($routeId <= 0 || $vehicleId <= 0) {
        $errorMsg = "Please select both a route and a vehicle to start your shift.";
    } else {
        try {
            // Create active shift in DB
            $stmt = $db->prepare("INSERT INTO shifts (driver_id, vehicle_id, route_id, status, start_time, occupancy) VALUES (?, ?, ?, 'Active', ?, 0)");
            $stmt->execute([$user['id'], $vehicleId, $routeId, date('Y-m-d H:i:s')]);
            header("Location: saffir_driver_home.php");
            exit;
        } catch (PDOException $e) {
            $errorMsg = "Error starting shift: " . $e->getMessage();
        }
    }
}

// Handle shift end
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_end_shift'])) {
    $shiftId = intval($_POST['shift_id'] ?? 0);
    try {
        $stmt = $db->prepare("UPDATE shifts SET status = 'Completed', end_time = ? WHERE id = ?");
        $stmt->execute([date('Y-m-d H:i:s'), $shiftId]);
        header("Location: saffir_driver_home.php");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Error ending shift: " . $e->getMessage();
    }
}

// Handle occupancy update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_occupancy'])) {
    $shiftId = intval($_POST['shift_id'] ?? 0);
    $occupancy = intval($_POST['occupancy'] ?? 0);
    try {
        $stmt = $db->prepare("UPDATE shifts SET occupancy = ? WHERE id = ?");
        $stmt->execute([$occupancy, $shiftId]);
        $successMsg = "Passenger occupancy level updated successfully!";
        // Refresh shift info
        header("Location: saffir_driver_home.php?success=1");
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Error updating occupancy: " . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $successMsg = "Passenger occupancy level updated successfully!";
}

// Fetch available routes and vehicles for the starting form
$routes = [];
$vehicles = [];
try {
    $routes = $db->query("SELECT * FROM routes")->fetchAll();
    $vehicles = $db->query("SELECT * FROM vehicles WHERE status = 'Active'")->fetchAll();
} catch (PDOException $e) {}

?>

<div class="container animate-slide-in">
    <div style="margin-bottom: 40px;">
        <h1>Driver Shift Console</h1>
        <p style="color: var(--grey-600);">Manage active shifts, update vehicle occupancy, and access route status logs.</p>
    </div>

    <?php if ($successMsg): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-xmark"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start; min-height: 400px;">
        
        <!-- Shift Status & Forms Card -->
        <div class="card" style="padding: 28px;">
            <?php if ($activeShift): ?>
                <!-- Active Shift Display -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--grey-200); padding-bottom: 16px; margin-bottom: 24px;">
                    <div>
                        <span class="badge badge-success" style="margin-bottom: 8px;"><i class="fa-solid fa-circle-play"></i> Active Shift</span>
                        <h3 style="color: var(--primary);"><?php echo htmlspecialchars($activeShift['line_name']); ?></h3>
                    </div>
                    <form method="POST" action="saffir_driver_home.php">
                        <input type="hidden" name="shift_id" value="<?php echo $activeShift['id']; ?>">
                        <button type="submit" name="action_end_shift" class="btn btn-primary" style="background-color: var(--error); min-height: 40px;">
                            <i class="fa-solid fa-circle-stop"></i> End Shift
                        </button>
                    </form>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; font-size: 0.95rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--grey-600);"><i class="fa-solid fa-bus"></i> Assigned Vehicle:</span>
                        <strong style="color: var(--primary);"><?php echo htmlspecialchars($activeShift['vehicle_type']); ?> (Cap: <?php echo $activeShift['capacity']; ?>)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: var(--grey-600);"><i class="fa-solid fa-clock"></i> Shift Started:</span>
                        <strong style="color: var(--primary);"><?php echo date('H:i', strtotime($activeShift['start_time'])); ?></strong>
                    </div>
                </div>

                <!-- Passenger Occupancy Form (Thesis Figure 6) -->
                <div style="border-top: 1px solid var(--grey-200); padding-top: 24px;">
                    <h4 style="margin-bottom: 16px; color: var(--primary);"><i class="fa-solid fa-users"></i> Live Occupancy Level</h4>
                    
                    <form method="POST" action="saffir_driver_home.php">
                        <input type="hidden" name="shift_id" value="<?php echo $activeShift['id']; ?>">
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 8px;">
                                <span>Current Density:</span>
                                <span id="densityVal" style="color: <?php echo $activeShift['occupancy'] >= 75 ? 'var(--error)' : 'var(--secondary)'; ?>;">
                                    <?php echo $activeShift['occupancy']; ?>% 
                                    (<?php echo $activeShift['occupancy'] >= 75 ? 'High Demand' : 'Normal'; ?>)
                                </span>
                            </div>
                            
                            <input type="range" class="form-control" name="occupancy" id="occupancyRange" min="0" max="100" step="5" value="<?php echo $activeShift['occupancy']; ?>" oninput="updateDensityText(this.value)" style="padding: 0; cursor: pointer; height: 12px; background-color: var(--grey-200);">
                        </div>
                        
                        <button type="submit" name="action_update_occupancy" class="btn btn-secondary" style="width: 100%;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Occupancy Level
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Start Shift Form -->
                <h3 style="margin-bottom: 24px; color: var(--primary);"><i class="fa-solid fa-key"></i> Start New Shift</h3>
                <form method="POST" action="saffir_driver_home.php">
                    <div class="form-group">
                        <label class="form-label" for="route_id">Select assigned route</label>
                        <select class="form-control" name="route_id" id="route_id" required>
                            <option value="">Select line route...</option>
                            <?php foreach ($routes as $r): ?>
                                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['line_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="vehicle_id">Select assigned vehicle</label>
                        <select class="form-control" name="vehicle_id" id="vehicle_id" required>
                            <option value="">Select bus/tram...</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['type']); ?> #<?php echo $v['id']; ?> (Capacity: <?php echo $v['capacity']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="action_start_shift" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                        <i class="fa-solid fa-circle-play"></i> Initialize Shift
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Live Route & Driver Performance Review -->
        <div style="display: flex; flex-direction: column; gap: 32px;">
            <!-- Next-Stop Countdown -->
            <div class="card" style="padding: 24px;">
                <h4 style="color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-bullhorn" style="color: var(--secondary);"></i>
                    Next Stop Broadcast
                </h4>
                <?php if ($activeShift): ?>
                    <div style="background-color: var(--grey-100); border-radius: var(--border-radius-md); padding: 16px; border-left: 4px solid var(--secondary);">
                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--primary);">Next: Seybouse Avenue</div>
                        <span style="font-size: 0.85rem; color: var(--grey-600);">Estimated arrival in: <strong>3 minutes</strong></span>
                    </div>
                <?php else: ?>
                    <p style="color: var(--grey-600); font-size: 0.9rem;">Start your shift to display the next arrival stop counters.</p>
                <?php endif; ?>
            </div>
            
            <!-- Performance Summary Card -->
            <div class="card" style="padding: 24px;">
                <h4 style="color: var(--primary); margin-bottom: 16px;"><i class="fa-solid fa-star" style="color: #FFC107;"></i> Driver Performance Summary</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="border-right: 1px solid var(--grey-200); padding-right: 16px;">
                        <span style="font-size: 0.8rem; color: var(--grey-600); text-transform: uppercase; font-weight: 600;">Shift Earnings</span>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 4px;">4,500 DA</div>
                    </div>
                    <div style="padding-left: 16px;">
                        <span style="font-size: 0.8rem; color: var(--grey-600); text-transform: uppercase; font-weight: 600;">Commuter Rating</span>
                        <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                            4.8 
                            <span style="font-size: 1rem; color: #FFC107;"><i class="fa-solid fa-star"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alerts box -->
            <div class="card" style="padding: 24px; border-top: 4px solid var(--error);">
                <h4 style="color: var(--error); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Traffic & Route Alerts
                </h4>
                <p style="font-size: 0.85rem; color: var(--grey-800);">
                    Roadworks reported on Sidi Brahim Interchange. Please follow detour signs. Schedules may experience up to 5 minutes delay.
                </p>
            </div>
        </div>
        
    </div>
</div>

<script>
function updateDensityText(val) {
    const textSpan = document.getElementById('densityVal');
    const label = val >= 75 ? 'High Demand' : 'Normal';
    textSpan.innerText = val + '% (' + label + ')';
    if (val >= 75) {
        textSpan.style.color = 'var(--error)';
    } else {
        textSpan.style.color = 'var(--secondary)';
    }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
