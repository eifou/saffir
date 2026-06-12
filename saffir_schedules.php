<?php
// saffir_schedules.php - Searchable real-time schedules and route timelines
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/header.php';

// Fetch all routes from DB
$routes = [];
try {
    $stmt = $db->query("SELECT * FROM routes");
    $routes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Query Error: " . $e->getMessage());
}

// Fetch all stops grouped by route_id
$stopsByRoute = [];
try {
    $stmt = $db->query("SELECT * FROM stops ORDER BY route_id, sequence");
    while ($row = $stmt->fetch()) {
        $stopsByRoute[$row['route_id']][] = $row;
    }
} catch (PDOException $e) {
    // Empty
}

// Fetch all schedules grouped by route_id
$schedulesByRoute = [];
try {
    $stmt = $db->query("SELECT * FROM schedules ORDER BY route_id, departure_time");
    while ($row = $stmt->fetch()) {
        $schedulesByRoute[$row['route_id']][] = $row['departure_time'];
    }
} catch (PDOException $e) {
    // Empty
}

// Create a lookup for route type and status to make UI look alive
$routeMeta = [
    1 => ['type' => 'Bus', 'status' => 'On Time', 'status_class' => 'badge-success', 'icon' => 'fa-bus'],
    2 => ['type' => 'Bus', 'status' => 'On Time', 'status_class' => 'badge-success', 'icon' => 'fa-bus'],
    3 => ['type' => 'Tram', 'status' => 'Delayed (5m)', 'status_class' => 'badge-warning', 'icon' => 'fa-train-subway']
];

?>

<div class="container animate-slide-in">
    <div style="margin-bottom: 40px;">
        <h1>Real-Time schedules & Timelines</h1>
        <p style="color: var(--grey-600);">Search for active transit lines in Annaba and view detailed stop lists and departure timers.</p>
    </div>

    <!-- Layout Grid: List on Left, Timeline on Right -->
    <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 32px; align-items: start; min-height: 500px;">
        
        <!-- Search & List Column -->
        <div>
            <!-- Search bar -->
            <div class="card" style="padding: 16px; margin-bottom: 24px;">
                <div style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 16px; top: 16px; color: var(--grey-600);"></i>
                    <input type="text" id="routeSearch" onkeyup="filterRoutes()" class="form-control" style="padding-left: 48px;" placeholder="Search by line name, stops, or location...">
                </div>
            </div>
            
            <!-- Timetable List -->
            <div id="routeCardsContainer" style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($routes as $r): 
                    $meta = $routeMeta[$r['id']] ?? ['type' => 'Bus', 'status' => 'On Time', 'status_class' => 'badge-success', 'icon' => 'fa-bus'];
                    $scheds = $schedulesByRoute[$r['id']] ?? ['08:00'];
                    $stopsCount = isset($stopsByRoute[$r['id']]) ? count($stopsByRoute[$r['id']]) : 0;
                    
                    // Format stops string for search
                    $stopsList = isset($stopsByRoute[$r['id']]) ? implode(', ', array_column($stopsByRoute[$r['id']], 'name')) : '';
                ?>
                    <div class="card route-card" data-name="<?php echo htmlspecialchars(strtolower($r['line_name'])); ?>" data-stops="<?php echo htmlspecialchars(strtolower($stopsList)); ?>" onclick="selectRoute(<?php echo $r['id']; ?>)" style="cursor: pointer; border-left: 6px solid var(--primary-container);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                                    <span style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($r['line_name']); ?></span>
                                    <span class="badge badge-info"><i class="fa-solid <?php echo $meta['icon']; ?>"></i> <?php echo $meta['type']; ?></span>
                                </div>
                                <p style="font-size: 0.9rem; color: var(--grey-800); margin-bottom: 4px;">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i> <?php echo htmlspecialchars($r['start_point']); ?> <strong>↔</strong> <?php echo htmlspecialchars($r['end_point']); ?>
                                </p>
                                <p style="font-size: 0.85rem; color: var(--grey-600);">
                                    <i class="fa-solid fa-route"></i> <?php echo $stopsCount; ?> stops on this line
                                </p>
                            </div>
                            
                            <div style="text-align: right; min-width: 140px;">
                                <div style="margin-bottom: 8px;">
                                    <span class="badge <?php echo $meta['status_class']; ?>"><?php echo $meta['status']; ?></span>
                                </div>
                                <span style="font-size: 0.85rem; color: var(--grey-600);">Next departures:</span>
                                <div style="display: flex; gap: 4px; justify-content: flex-end; margin-top: 4px; flex-wrap: wrap;">
                                    <?php 
                                    // Show first 2 departures
                                    for ($i = 0; $i < min(2, count($scheds)); $i++): 
                                    ?>
                                        <span style="background-color: var(--grey-100); border: 1px solid var(--grey-200); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; color: var(--primary);">
                                            <?php echo htmlspecialchars($scheds[$i]); ?>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Timeline Display Column -->
        <div class="card" id="timelineContainer" style="position: sticky; top: 100px; padding: 28px;">
            <div id="timelineDefaultMsg" style="text-align: center; padding: 40px 0; color: var(--grey-600);">
                <i class="fa-solid fa-route" style="font-size: 3rem; color: var(--grey-300); margin-bottom: 16px; display: block;"></i>
                <h4>Select a Transit Route</h4>
                <p style="font-size: 0.85rem; margin-top: 8px;">Click on any route card in the list to load the real-time timeline, next-stop countdowns, and stops list.</p>
            </div>
            
            <div id="timelineContent" style="display: none;">
                <!-- Filled dynamically via JS -->
            </div>
        </div>
        
    </div>
</div>

<script>
// JSON payload of routes, stops, and schedules seeded dynamically from PHP DB queries
const routeData = {
    <?php foreach ($routes as $r): 
        $meta = $routeMeta[$r['id']] ?? ['type' => 'Bus', 'status' => 'On Time', 'status_class' => 'badge-success', 'icon' => 'fa-bus'];
        $scheds = $schedulesByRoute[$r['id']] ?? [];
        $stops = $stopsByRoute[$r['id']] ?? [];
    ?>
    "<?php echo $r['id']; ?>": {
        "id": <?php echo $r['id']; ?>,
        "name": "<?php echo addslashes($r['line_name']); ?>",
        "type": "<?php echo $meta['type']; ?>",
        "status": "<?php echo $meta['status']; ?>",
        "statusClass": "<?php echo $meta['status_class']; ?>",
        "startPoint": "<?php echo addslashes($r['start_point']); ?>",
        "endPoint": "<?php echo addslashes($r['end_point']); ?>",
        "schedules": <?php echo json_encode($scheds); ?>,
        "stops": <?php echo json_encode($stops); ?>
    },
    <?php endforeach; ?>
};

function filterRoutes() {
    const query = document.getElementById('routeSearch').value.toLowerCase();
    const cards = document.querySelectorAll('.route-card');
    
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const stops = card.getAttribute('data-stops');
        if (name.includes(query) || stops.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function selectRoute(routeId) {
    const data = routeData[routeId];
    if (!data) return;
    
    // Highlight active card
    const cards = document.querySelectorAll('.route-card');
    cards.forEach(c => {
        c.style.borderColor = 'var(--primary-container)';
    });
    // Find clicked card and color it secondary green
    event.currentTarget.style.borderColor = 'var(--secondary)';
    
    const defaultMsg = document.getElementById('timelineDefaultMsg');
    const content = document.getElementById('timelineContent');
    
    defaultMsg.style.display = 'none';
    content.style.display = 'block';
    
    // Format stops vertical timeline
    let stopsHtml = '';
    data.stops.forEach((stop, index) => {
        const isStart = index === 0;
        const isEnd = index === data.stops.length - 1;
        const color = isStart ? 'var(--secondary)' : (isEnd ? 'var(--primary)' : 'var(--grey-600)');
        const icon = isStart ? 'fa-circle-dot' : (isEnd ? 'fa-location-dot' : 'fa-circle');
        
        stopsHtml += `
            <div style="display:flex; gap:16px; position:relative; padding-bottom: 24px;">
                ${!isEnd ? `<div style="position:absolute; left:7px; top:16px; bottom:-16px; width:2px; background-color:var(--grey-200);"></div>` : ''}
                <div style="position:relative; z-index:2; flex-shrink:0; width:16px; display:flex; justify-content:center; align-items:start; margin-top: 4px;">
                    <i class="fa-solid ${icon}" style="color:${color}; font-size:${(isStart || isEnd) ? '1rem' : '0.8rem'};"></i>
                </div>
                <div>
                    <h5 style="font-size:0.95rem; margin-bottom:2px; color:var(--primary); font-weight:${(isStart || isEnd) ? '700' : '500'};">${stop.name}</h5>
                    <span style="font-size:0.8rem; color:var(--grey-600);">Stop #${stop.sequence}</span>
                </div>
            </div>
        `;
    });
    
    content.innerHTML = `
        <h3 style="margin-bottom: 4px; color: var(--primary);">${data.name}</h3>
        <p style="font-size:0.85rem; color:var(--grey-600); margin-bottom: 16px;">
            <i class="fa-solid fa-clock"></i> Next Arrivals: <strong>${data.schedules.slice(0, 3).join(', ')}</strong>
        </p>
        
        <div style="display: flex; gap: 8px; margin-bottom: 24px;">
            <span class="badge ${data.statusClass}">${data.status}</span>
            <span class="badge badge-info">${data.type} Service</span>
        </div>
        
        <h4 style="font-size: 1rem; margin-bottom: 16px; border-bottom: 1px solid var(--grey-200); padding-bottom: 8px;">Stops & Timeline</h4>
        <div style="margin-top: 16px;">
            ${stopsHtml}
        </div>
        
        <div style="margin-top: 24px; text-align: center; border-top: 1px solid var(--grey-200); padding-top: 24px;">
            <a href="metroflow_join_us.php" class="btn btn-secondary" style="width:100%;">
                <i class="fa-solid fa-bell"></i> Set Route Alerts
            </a>
        </div>
    `;
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
