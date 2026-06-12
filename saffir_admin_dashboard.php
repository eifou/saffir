<?php
// saffir_admin_dashboard.php - Admin Dashboard
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/header.php';

requireRole('admin');

$user = getCurrentUser();
$errorMsg = null;
$successMsg = null;

// Handle Add Vehicle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_vehicle'])) {
    $type = $_POST['type'] ?? 'Bus';
    $capacity = intval($_POST['capacity'] ?? 50);
    $status = $_POST['status'] ?? 'Active';
    
    if ($capacity <= 0) {
        $errorMsg = "Vehicle capacity must be greater than zero.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO vehicles (type, capacity, status) VALUES (?, ?, ?)");
            $stmt->execute([$type, $capacity, $status]);
            $successMsg = "Vehicle successfully added to active fleet inventory!";
        } catch (PDOException $e) {
            $errorMsg = "Failed to add vehicle: " . $e->getMessage();
        }
    }
}

// Fetch active vehicles count
$activeVehiclesCount = 1248; // Mock default starting base + query count
try {
    $dbVehicles = $db->query("SELECT COUNT(*) FROM vehicles WHERE status='Active'")->fetchColumn();
    $activeVehiclesCount += $dbVehicles - 3; // Offset default seeded active count to keep thesis numbers
} catch (PDOException $e) {}

// Fetch active shifts list
$shiftsList = [];
try {
    $stmt = $db->query("
        SELECT s.*, u.name as driver_name, v.type as vehicle_type, r.line_name 
        FROM shifts s
        JOIN users u ON s.driver_id = u.id
        JOIN vehicles v ON s.vehicle_id = v.id
        JOIN routes r ON s.route_id = r.id
        ORDER BY s.id DESC
    ");
    $shiftsList = $stmt->fetchAll();
} catch (PDOException $e) {}

require_once __DIR__ . '/header.php';
?>

<div class="container animate-slide-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1>Network Health Dashboard</h1>
            <p style="color: var(--grey-600);">Real-time metrics, fleet inventory control, and live status of transit lines in Annaba.</p>
        </div>
        <div>
            <span class="badge badge-success" style="padding: 8px 16px; font-size: 0.9rem;">
                <i class="fa-solid fa-circle-nodes"></i> System Status: Operational
            </span>
        </div>
    </div>
    
    <?php if ($successMsg): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-xmark"></i> <?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <!-- KPI Section (Thesis Figure 5) -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <span class="dashboard-card-title">Active Vehicles</span>
            <div class="dashboard-card-value"><?php echo number_format($activeVehiclesCount); ?></div>
            <span class="dashboard-card-meta"><span style="color: var(--secondary);"><i class="fa-solid fa-caret-up"></i> +12%</span> vs last month</span>
        </div>
        <div class="dashboard-card">
            <span class="dashboard-card-title">On-Time Performance</span>
            <div class="dashboard-card-value">94.2%</div>
            <span class="dashboard-card-meta"><span style="color: var(--secondary);"><i class="fa-solid fa-caret-up"></i> +0.4%</span> target is 95%</span>
        </div>
        <div class="dashboard-card card-success">
            <span class="dashboard-card-title">Daily Ridership</span>
            <div class="dashboard-card-value">42,100</div>
            <span class="dashboard-card-meta"><span style="color: var(--secondary);"><i class="fa-solid fa-users"></i> Commuters</span> active today</span>
        </div>
        <div class="dashboard-card card-success">
            <span class="dashboard-card-title">Daily Revenue</span>
            <div class="dashboard-card-value">$182,000</div>
            <span class="dashboard-card-meta"><span style="color: var(--secondary);"><i class="fa-solid fa-dollar-sign"></i> USD equivalent</span> ticket sales</span>
        </div>
    </div>

    <!-- Live Map & Charts Grid -->
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; margin-bottom: 48px; align-items: start;">
        
        <!-- Live Network Map Simulation Card -->
        <div class="card" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-earth-africa" style="color: var(--primary-light);"></i>
                    Live Network Map
                </h3>
                <span class="badge badge-success" style="animation: pulse-soft 2s infinite;"><i class="fa-solid fa-wifi"></i> Live GPS Feed</span>
            </div>
            
            <!-- SVG Map Styling and Keyframe Animations -->
            <style>
                @keyframes busMove1 {
                    0% { stroke-dashoffset: 400; }
                    100% { stroke-dashoffset: 0; }
                }
                @keyframes busMove2 {
                    0% { stroke-dashoffset: 0; }
                    100% { stroke-dashoffset: 300; }
                }
                .map-route-10 {
                    stroke-dasharray: 10, 5;
                    animation: busMove1 20s linear infinite;
                }
                .map-route-4 {
                    stroke-dasharray: 12, 6;
                    animation: busMove2 16s linear infinite;
                }
                .map-pulse {
                    animation: pulse-soft 2s infinite ease-in-out;
                }
            </style>
            
            <div style="background-color: var(--grey-800); border-radius: var(--border-radius-md); padding: 16px; display: flex; justify-content: center; position: relative; overflow: hidden; box-shadow: inset 0 2px 8px rgba(0,0,0,0.5);">
                <svg viewBox="0 0 600 350" width="100%" height="auto" style="max-height: 350px;">
                    <!-- Grid background -->
                    <defs>
                        <pattern id="grid" width="30" height="30" patternUnits="userSpaceOnUse">
                            <path d="M 30 0 L 0 0 0 30" fill="none" stroke="rgba(255, 255, 255, 0.05)" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                    
                    <!-- Route Paths -->
                    <!-- Line 10 (Annaba Center - El Bouni) -->
                    <path d="M 50 150 Q 200 120 300 200 T 550 220" fill="none" stroke="var(--secondary-container)" stroke-width="4" class="map-route-10" />
                    <!-- Line 4 (Sidi Amar - University) -->
                    <path d="M 100 50 L 300 120 L 450 300" fill="none" stroke="#38bdf8" stroke-width="4" class="map-route-4" />
                    <!-- Line 15 (Seraidi Road) -->
                    <path d="M 50 150 Q 150 250 250 300" fill="none" stroke="#f43f5e" stroke-width="3" stroke-dasharray="6, 4" />
                    
                    <!-- Major Hub nodes -->
                    <!-- Annaba Center (Cours de la Revolution) -->
                    <circle cx="50" cy="150" r="10" fill="var(--primary)" stroke="var(--white)" stroke-width="2" class="map-pulse" />
                    <text x="50" y="132" fill="var(--white)" font-size="10" font-weight="bold" text-anchor="middle">Annaba Center</text>
                    
                    <!-- University Hub -->
                    <circle cx="450" cy="300" r="10" fill="var(--primary)" stroke="var(--white)" stroke-width="2" />
                    <text x="450" y="322" fill="var(--white)" font-size="10" font-weight="bold" text-anchor="middle">University Campus</text>
                    
                    <!-- El Bouni -->
                    <circle cx="550" cy="220" r="8" fill="var(--primary)" stroke="var(--white)" stroke-width="2" />
                    <text x="550" y="240" fill="var(--white)" font-size="10" font-weight="bold" text-anchor="middle">El Bouni</text>
                    
                    <!-- Seraidi Town -->
                    <circle cx="250" cy="300" r="8" fill="var(--primary)" stroke="var(--white)" stroke-width="2" />
                    <text x="250" y="320" fill="var(--white)" font-size="10" font-weight="bold" text-anchor="middle">Seraidi Town</text>
                    
                    <!-- Active GPS Vehicles indicators (simulated coordinates) -->
                    <circle cx="210" cy="145" r="7" fill="var(--secondary-container)" stroke="var(--primary)" stroke-width="1.5">
                        <animate attributeName="cx" values="210;230;250;210" dur="8s" repeatCount="indefinite" />
                        <animate attributeName="cy" values="145;155;165;145" dur="8s" repeatCount="indefinite" />
                    </circle>
                    <circle cx="330" cy="155" r="7" fill="#38bdf8" stroke="var(--primary)" stroke-width="1.5">
                        <animate attributeName="cx" values="330;360;390;330" dur="10s" repeatCount="indefinite" />
                        <animate attributeName="cy" values="155;190;225;155" dur="10s" repeatCount="indefinite" />
                    </circle>
                </svg>
                
                <!-- Map Legend -->
                <div style="position: absolute; bottom: 12px; left: 12px; background: rgba(0,30,64,0.9); border-radius: var(--border-radius-sm); padding: 8px 12px; display: flex; flex-direction: column; gap: 4px; border: 1px solid rgba(255,255,255,0.1);">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--white);">
                        <span style="display: inline-block; width: 12px; height: 3px; background-color: var(--secondary-container);"></span>
                        Line 10 (El Bouni)
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--white);">
                        <span style="display: inline-block; width: 12px; height: 3px; background-color: #38bdf8;"></span>
                        Line 4 (University)
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.75rem; color: var(--white);">
                        <span style="display: inline-block; width: 12px; height: 3px; background-color: #f43f5e; border-style: dashed;"></span>
                        Line 15 (Seraidi Mountain)
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fleet Composition & Analytics Card -->
        <div class="card" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%;">
            <div>
                <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-chart-pie" style="color: var(--primary-light);"></i>
                    Fleet Composition & Metrics
                </h3>
                
                <!-- Composition Bars -->
                <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">
                            <span>Buses</span>
                            <span>60% (748 Active)</span>
                        </div>
                        <div style="width: 100%; height: 10px; background-color: var(--grey-200); border-radius: 5px; overflow: hidden;">
                            <div style="width: 60%; height: 100%; background-color: var(--primary);"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">
                            <span>Trams</span>
                            <span>30% (374 Active)</span>
                        </div>
                        <div style="width: 100%; height: 10px; background-color: var(--grey-200); border-radius: 5px; overflow: hidden;">
                            <div style="width: 30%; height: 100%; background-color: var(--secondary);"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">
                            <span>Metro (Underground)</span>
                            <span>10% (126 Active)</span>
                        </div>
                        <div style="width: 100%; height: 10px; background-color: var(--grey-200); border-radius: 5px; overflow: hidden;">
                            <div style="width: 10%; height: 100%; background-color: #38bdf8;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Revenue SVG Chart (Thesis Figure 5 reference) -->
            <div>
                <h4 style="font-size: 0.95rem; color: var(--primary); margin-bottom: 12px;">Weekly Ridership Trend (thousands)</h4>
                <div style="background-color: var(--grey-100); border-radius: var(--border-radius-md); padding: 8px; text-align: center;">
                    <svg viewBox="0 0 250 80" width="100%" height="auto" style="max-height: 80px;">
                        <!-- Grid Lines -->
                        <line x1="0" y1="20" x2="250" y2="20" stroke="var(--grey-200)" stroke-width="1" />
                        <line x1="0" y1="50" x2="250" y2="50" stroke="var(--grey-200)" stroke-width="1" />
                        <!-- Line Chart representing ridership -->
                        <path d="M 0 60 L 40 45 L 80 52 L 120 30 L 160 25 L 200 48 L 250 15" fill="none" stroke="var(--secondary)" stroke-width="3" />
                        <!-- Area gradient fill -->
                        <path d="M 0 60 L 40 45 L 80 52 L 120 30 L 160 25 L 200 48 L 250 15 L 250 80 L 0 80 Z" fill="rgba(0, 109, 55, 0.08)" />
                        <!-- Circles at points -->
                        <circle cx="120" cy="30" r="4" fill="var(--primary)" />
                        <circle cx="250" cy="15" r="4" fill="var(--primary)" />
                    </svg>
                    <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--grey-600); margin-top: 4px; padding: 0 4px;">
                        <span>Mon</span>
                        <span>Wed</span>
                        <span>Fri</span>
                        <span>Sun</span>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Active Driver Shift Control & Fleet Insertion Form -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; margin-bottom: 48px;">
        
        <!-- Add Vehicle to Inventory Form -->
        <div class="card" style="padding: 28px;">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px; color: var(--primary);">
                <i class="fa-solid fa-plus-minus" style="color: var(--primary-light);"></i>
                Register Fleet Vehicle
            </h3>
            
            <form method="POST" action="saffir_admin_dashboard.php">
                <div class="form-group">
                    <label class="form-label" for="vehType">Vehicle Mode Type</label>
                    <select class="form-control" id="vehType" name="type" required>
                        <option value="Bus">Urban Commuting Bus</option>
                        <option value="Tram">Tramway Carriage</option>
                        <option value="Metro">Subway/Metro Carriage</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="capacity">Passenger Carrying Capacity</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" min="10" max="300" value="50" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Initial Status</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="Active">Active Duty</option>
                        <option value="Maintenance">Maintenance Inspection</option>
                        <option value="Inactive">Out of Service</option>
                    </select>
                </div>
                
                <button type="submit" name="action_add_vehicle" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                    <i class="fa-solid fa-folder-plus"></i> Add Vehicle to Database
                </button>
            </form>
        </div>
        
        <!-- Active Driver Shifts Log -->
        <div class="card" style="padding: 28px; display: flex; flex-direction: column;">
            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 8px; color: var(--primary);">
                <i class="fa-solid fa-user-clock" style="color: var(--primary-light);"></i>
                Active Driver Shifts
            </h3>
            
            <div class="table-responsive" style="flex: 1;">
                <table style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th>Driver</th>
                            <th>Route Line</th>
                            <th>Occupancy</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shiftsList)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--grey-600); padding: 24px;">No active driver shifts logged.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shiftsList as $s): 
                                $statusClass = ($s['status'] === 'Active') ? 'badge-success' : 'badge-info';
                                $occClass = ($s['occupancy'] >= 75) ? 'badge-danger' : 'badge-success';
                            ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($s['driver_name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['line_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $occClass; ?>">
                                            <?php echo htmlspecialchars($s['occupancy']); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($s['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
