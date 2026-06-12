<?php
// saffir_home_animated.php - Public home page with trip planner
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/header.php';

// Fetch all unique stops to populate Trip Planner inputs
$stops = [];
try {
    $stmt = $db->query("SELECT DISTINCT name FROM stops ORDER BY name ASC");
    $stops = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Fallback if db query fails
    $stops = ['Cours de la Révolution', 'Pont Blanc', 'Sidi Brahim Interchange', 'El Bouni Center', 'Plaine Ouest Terminal', 'Seybouse Avenue', 'Sidi Amar Town Centre', 'Badji Mokhtar University Campus', 'Seraidi Road Checkpoint', 'Seraidi Town Square'];
}
?>

<!-- ═══════════════════════════════════════════
     HERO SECTION — Slate gradient, centered
═══════════════════════════════════════════ -->
<div class="hero">
    <div class="hero-centered animate-slide-in">
        <h1>The Future of Your<br>Daily Commute</h1>
        <p>Efficient, sustainable, and connected transport at your fingertips.<br>
           Discover the rhythm of the city with the most advanced transit network.</p>
        <a href="#planner" class="btn-hero-cta">
            <i class="fa-solid fa-compass"></i> Start Journey
        </a>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     FLOATING TRIP PLANNER BAR
═══════════════════════════════════════════ -->
<div class="trip-planner-bar" id="planner">
    <div class="trip-planner-card">

        <!-- From -->
        <div class="trip-input-group">
            <label for="origin">From</label>
            <div class="trip-input-wrapper">
                <i class="fa-solid fa-location-crosshairs"></i>
                <select class="trip-input" id="origin" required>
                    <option value="">Current Location</option>
                    <?php foreach ($stops as $s): ?>
                        <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- To -->
        <div class="trip-input-group">
            <label for="destination">To</label>
            <div class="trip-input-wrapper">
                <i class="fa-solid fa-location-dot"></i>
                <select class="trip-input" id="destination" required>
                    <option value="">Where to?</option>
                    <?php foreach ($stops as $s): ?>
                        <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Time -->
        <div class="trip-input-group">
            <label for="depTime">Time</label>
            <div class="trip-input-wrapper">
                <i class="fa-regular fa-clock"></i>
                <input type="time" class="trip-input" id="depTime" value="<?php echo date('H:i'); ?>">
            </div>
        </div>

        <!-- Find Route Button -->
        <button type="button" class="btn-find-route" onclick="planTrip()">
            <i class="fa-solid fa-magnifying-glass"></i> Find Route
        </button>

    </div>

    <!-- Results Container (hidden by default) -->
    <div id="plannerResults" style="display: none; margin-top: 24px;">
        <div id="resultsContent"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     KEY VALUE PROPOSITIONS
═══════════════════════════════════════════ -->
<div class="container" style="padding: 96px 24px 80px 24px;">
    <h2 style="text-align: center; margin-bottom: 12px;">Why Choose Saffir?</h2>
    <p style="text-align: center; color: var(--grey-600); max-width: 560px; margin: 0 auto 56px auto; font-size: 1.05rem;">
        Built for the modern commuter. Smart, seamless, and sustainable.
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px;">

        <!-- Card 1 -->
        <div class="card" style="text-align: center; padding: 40px 28px;">
            <div style="width: 72px; height: 72px; background-color: var(--secondary-light); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto; font-size: 1.75rem;">
                <i class="fa-solid fa-leaf"></i>
            </div>
            <h3 style="margin-bottom: 12px;">Eco-Friendly Travel</h3>
            <p>Optimized routing and smart load balancing minimize fuel consumption. Track your carbon savings on every trip.</p>
        </div>

        <!-- Card 2 -->
        <div class="card" style="text-align: center; padding: 40px 28px;">
            <div style="width: 72px; height: 72px; background-color: rgba(13, 75, 133, 0.08); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto; font-size: 1.75rem;">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <h3 style="margin-bottom: 12px;">Smart Subscriptions</h3>
            <p>No more cash hassle. Purchase student, commuter, or corporate monthly passes via your secure digital wallet.</p>
        </div>

        <!-- Card 3 -->
        <div class="card" style="text-align: center; padding: 40px 28px;">
            <div style="width: 72px; height: 72px; background-color: var(--error-container); color: var(--error); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto; font-size: 1.75rem;">
                <i class="fa-solid fa-location-crosshairs"></i>
            </div>
            <h3 style="margin-bottom: 12px;">Real-Time Tracking</h3>
            <p>Precise route timings, live stop updates, and instant delay alerts — always know exactly where your bus is.</p>
        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════
     NETWORK ALERT TICKER
═══════════════════════════════════════════ -->
<div style="background-color: var(--primary); color: var(--white); padding: 18px 0; border-top: 3px solid var(--secondary);">
    <div class="container" style="display: flex; align-items: center; gap: 16px; overflow: hidden;">
        <span class="badge badge-danger" style="animation: pulse-soft 2s infinite; flex-shrink: 0;">
            <i class="fa-solid fa-circle-exclamation"></i> Network Alert
        </span>
        <marquee scrollamount="5" style="font-size: 0.9rem; font-weight: 500;">
            [Line 15 Annaba - Seraidi]: Reduced capacity scheduled for Sunday due to routine vehicle maintenance. &nbsp;|&nbsp; [Line 4 Sidi Amar]: All university services running on-time. Extra morning services added. &nbsp;|&nbsp; [System]: Wallet integration successfully enabled. Passenger top-ups active.
        </marquee>
    </div>
</div>

<script>
// Client-side Javascript Trip Planner Simulation using seeded DB data structure
function planTrip() {
    const origin = document.getElementById('origin').value;
    const dest = document.getElementById('destination').value;
    const depTime = document.getElementById('depTime').value;

    const resultsDiv = document.getElementById('plannerResults');
    const contentDiv = document.getElementById('resultsContent');

    if (!origin || !dest) {
        resultsDiv.style.display = 'block';
        contentDiv.innerHTML = `
            <div class="alert alert-warning">
                <i class="fa-solid fa-circle-exclamation"></i> Please select both a departure and a destination stop.
            </div>
        `;
        return;
    }

    if (origin === dest) {
        resultsDiv.style.display = 'block';
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-xmark"></i> Departure and arrival points cannot be the same stop.
            </div>
        `;
        return;
    }

    resultsDiv.style.display = 'block';
    contentDiv.innerHTML = `<p style="color:var(--grey-600);"><i class="fa-solid fa-spinner fa-spin"></i> Finding routes...</p>`;

    // Simulate API query delay
    setTimeout(() => {
        // Mock routes matching seeded DB — Line 10 (1), Line 4 (2), Line 15 (3)
        const mockStops = {
            'Line 10 — Annaba Center to El Bouni': ['Cours de la Révolution', 'Pont Blanc', 'Sidi Brahim Interchange', 'El Bouni Center'],
            'Line 4 — Sidi Amar to Badji Mokhtar University': ['Plaine Ouest Terminal', 'Seybouse Avenue', 'Sidi Amar Town Centre', 'Badji Mokhtar University Campus'],
            'Line 15 — Annaba Center to Seraidi Heights': ['Cours de la Révolution', 'Seraidi Road Checkpoint', 'Seraidi Town Square']
        };

        let pathsFound = [];

        for (const [routeName, stops] of Object.entries(mockStops)) {
            const originIndex = stops.indexOf(origin);
            const destIndex = stops.indexOf(dest);

            if (originIndex !== -1 && destIndex !== -1 && originIndex < destIndex) {
                const stopsCount = destIndex - originIndex;
                const duration = stopsCount * 12;
                const carbonSaved = (stopsCount * 0.45).toFixed(2);
                const ticketPrice = stopsCount * 15;

                pathsFound.push({
                    name: routeName,
                    stops: stops.slice(originIndex, destIndex + 1),
                    duration: duration,
                    carbonSaved: carbonSaved,
                    price: ticketPrice
                });
            }
        }

        if (pathsFound.length === 0) {
            contentDiv.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fa-solid fa-circle-exclamation"></i> No direct route found between <strong>${origin}</strong> and <strong>${dest}</strong>. Please check our Schedules page for connections.
                </div>
            `;
        } else {
            let html = '<div style="display:flex; flex-direction:column; gap:20px;">';
            pathsFound.forEach(p => {
                html += `
                    <div class="card" style="border-left: 6px solid var(--secondary); background: var(--grey-100);">
                        <div style="display:flex; justify-content:space-between; align-items:start; flex-wrap:wrap; gap:16px; margin-bottom:16px;">
                            <div>
                                <h4 style="color:var(--primary); font-size:1.1rem;">${p.name}</h4>
                                <p style="font-size:0.85rem; color:var(--grey-600); margin-top:4px;">
                                    <i class="fa-solid fa-clock"></i> Departure scheduled around: <strong>${depTime}</strong>
                                </p>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <span class="badge badge-success"><i class="fa-solid fa-leaf"></i> -${p.carbonSaved} kg CO₂</span>
                                <span class="badge badge-info"><i class="fa-solid fa-ticket"></i> ${p.price} DA</span>
                            </div>
                        </div>

                        <!-- Stop timeline -->
                        <div style="display:flex; flex-direction:column; gap:8px; position:relative; padding-left:24px; margin-bottom:16px;">
                            <div style="position:absolute; left:7px; top:6px; bottom:6px; width:2px; background-color:var(--primary-container);"></div>
                            ${p.stops.map((stop, i) => {
                                const isStart = i === 0;
                                const isEnd = i === p.stops.length - 1;
                                const color = isStart ? 'var(--secondary)' : (isEnd ? 'var(--primary)' : 'var(--grey-600)');
                                const icon = isStart ? 'fa-circle-dot' : (isEnd ? 'fa-location-dot' : 'fa-circle');
                                return `
                                    <div style="display:flex; align-items:center; gap:12px; font-size:0.9rem;">
                                        <i class="fa-solid ${icon}" style="color:${color}; position:absolute; left:0; width:16px; text-align:center;"></i>
                                        <span style="font-weight:${(isStart || isEnd) ? '600' : '400'}">${stop}</span>
                                    </div>
                                `;
                            }).join('')}
                        </div>

                        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--grey-200); padding-top:16px;">
                            <span style="font-size:0.9rem; color:var(--grey-800);">Estimated travel time: <strong>${p.duration} mins</strong></span>
                            <a href="metroflow_join_us.php" class="btn btn-primary" style="min-height:36px; padding:0 16px; font-size:0.85rem;">
                                <i class="fa-solid fa-ticket"></i> Book Ticket
                            </a>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            contentDiv.innerHTML = html;
        }
    }, 600);
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
