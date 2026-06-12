<?php
// db.php - Database connection and initialization helper using PDO SQLite

$dbPath = __DIR__ . '/saffir.db';
$dbExists = file_exists($dbPath);

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys
    $db->exec("PRAGMA foreign_keys = ON;");
    
    // Create tables if they do not exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT NOT NULL CHECK(role IN ('admin', 'driver', 'passenger')),
            email TEXT UNIQUE NOT NULL,
            name TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            wallet_balance REAL DEFAULT 0.0
        );

        CREATE TABLE IF NOT EXISTS vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL CHECK(type IN ('Bus', 'Tram', 'Metro')),
            capacity INTEGER NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('Active', 'Maintenance', 'Inactive'))
        );

        CREATE TABLE IF NOT EXISTS routes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            line_name TEXT NOT NULL,
            start_point TEXT NOT NULL,
            end_point TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS stops (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            route_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            sequence INTEGER NOT NULL,
            FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            route_id INTEGER NOT NULL,
            departure_time TEXT NOT NULL,
            FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS shifts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            driver_id INTEGER NOT NULL,
            vehicle_id INTEGER NOT NULL,
            route_id INTEGER NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('Assigned', 'Active', 'Completed')),
            start_time TEXT,
            end_time TEXT,
            occupancy INTEGER DEFAULT 0,
            FOREIGN KEY(driver_id) REFERENCES users(id),
            FOREIGN KEY(vehicle_id) REFERENCES vehicles(id),
            FOREIGN KEY(route_id) REFERENCES routes(id)
        );

        CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            passenger_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            valid_until TEXT NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('Active', 'Expired', 'Cancelled')),
            FOREIGN KEY(passenger_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            passenger_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            date TEXT NOT NULL,
            type TEXT NOT NULL CHECK(type IN ('Wallet Top-up', 'Subscription Purchase', 'Ticket Fare')),
            FOREIGN KEY(passenger_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    // Check if table contains users, if not, seed default data
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();

    if ($userCount == 0) {
        // Seed default users
        $usersToSeed = [
            [
                'role' => 'admin',
                'email' => 'admin@saffir.com',
                'name' => 'SAFFIR Administrator',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'wallet_balance' => 0.0
            ],
            [
                'role' => 'driver',
                'email' => 'driver@saffir.com',
                'name' => 'Prof. Youcef Gharghout',
                'password_hash' => password_hash('driver123', PASSWORD_DEFAULT),
                'wallet_balance' => 0.0
            ],
            [
                'role' => 'passenger',
                'email' => 'passenger@saffir.com',
                'name' => 'Yasser Seif Eddine',
                'password_hash' => password_hash('passenger123', PASSWORD_DEFAULT),
                'wallet_balance' => 42.50 // Match thesis page 15 ($42.50)
            ]
        ];

        $insertUser = $db->prepare("INSERT INTO users (role, email, name, password_hash, wallet_balance) VALUES (:role, :email, :name, :password_hash, :wallet_balance)");
        foreach ($usersToSeed as $u) {
            $insertUser->execute($u);
        }

        // Seed vehicles
        $vehiclesToSeed = [
            ['type' => 'Bus', 'capacity' => 50, 'status' => 'Active'],
            ['type' => 'Bus', 'capacity' => 50, 'status' => 'Active'],
            ['type' => 'Tram', 'capacity' => 150, 'status' => 'Active'],
            ['type' => 'Bus', 'capacity' => 40, 'status' => 'Maintenance']
        ];
        $insertVehicle = $db->prepare("INSERT INTO vehicles (type, capacity, status) VALUES (?, ?, ?)");
        foreach ($vehiclesToSeed as $v) {
            $insertVehicle->execute([$v['type'], $v['capacity'], $v['status']]);
        }

        // Seed routes
        $routesToSeed = [
            ['line_name' => 'Line 10 - Annaba Center to El Bouni', 'start_point' => 'Cours de la Révolution', 'end_point' => 'El Bouni Center'],
            ['line_name' => 'Line 4 - Sidi Amar to Badji Mokhtar University', 'start_point' => 'Plaine Ouest', 'end_point' => 'University Campus'],
            ['line_name' => 'Line 15 - Annaba Center to Seraidi Heights', 'start_point' => 'Cours de la Révolution', 'end_point' => 'Seraidi Town']
        ];
        $insertRoute = $db->prepare("INSERT INTO routes (line_name, start_point, end_point) VALUES (?, ?, ?)");
        foreach ($routesToSeed as $r) {
            $insertRoute->execute([$r['line_name'], $r['start_point'], $r['end_point']]);
        }

        // Seed stops
        $stopsToSeed = [
            // Line 10
            ['route_id' => 1, 'name' => 'Cours de la Révolution', 'sequence' => 1],
            ['route_id' => 1, 'name' => 'Pont Blanc', 'sequence' => 2],
            ['route_id' => 1, 'name' => 'Sidi Brahim Interchange', 'sequence' => 3],
            ['route_id' => 1, 'name' => 'El Bouni Center', 'sequence' => 4],
            // Line 4
            ['route_id' => 2, 'name' => 'Plaine Ouest Terminal', 'sequence' => 1],
            ['route_id' => 2, 'name' => 'Seybouse Avenue', 'sequence' => 2],
            ['route_id' => 2, 'name' => 'Sidi Amar Town Centre', 'sequence' => 3],
            ['route_id' => 2, 'name' => 'Badji Mokhtar University Campus', 'sequence' => 4],
            // Line 15
            ['route_id' => 3, 'name' => 'Cours de la Révolution', 'sequence' => 1],
            ['route_id' => 3, 'name' => 'Seraidi Road Checkpoint', 'sequence' => 2],
            ['route_id' => 3, 'name' => 'Seraidi Town Square', 'sequence' => 3]
        ];
        $insertStop = $db->prepare("INSERT INTO stops (route_id, name, sequence) VALUES (?, ?, ?)");
        foreach ($stopsToSeed as $s) {
            $insertStop->execute([$s['route_id'], $s['name'], $s['sequence']]);
        }

        // Seed schedules
        $schedulesToSeed = [
            ['route_id' => 1, 'departure_time' => '07:30'],
            ['route_id' => 1, 'departure_time' => '08:30'],
            ['route_id' => 1, 'departure_time' => '10:30'],
            ['route_id' => 1, 'departure_time' => '12:30'],
            ['route_id' => 1, 'departure_time' => '14:30'],
            ['route_id' => 1, 'departure_time' => '16:30'],
            ['route_id' => 1, 'departure_time' => '18:30'],
            
            ['route_id' => 2, 'departure_time' => '07:00'],
            ['route_id' => 2, 'departure_time' => '08:00'],
            ['route_id' => 2, 'departure_time' => '09:00'],
            ['route_id' => 2, 'departure_time' => '11:00'],
            ['route_id' => 2, 'departure_time' => '13:00'],
            ['route_id' => 2, 'departure_time' => '15:00'],
            ['route_id' => 2, 'departure_time' => '17:00'],
            
            ['route_id' => 3, 'departure_time' => '08:00'],
            ['route_id' => 3, 'departure_time' => '10:00'],
            ['route_id' => 3, 'departure_time' => '12:00'],
            ['route_id' => 3, 'departure_time' => '14:00'],
            ['route_id' => 3, 'departure_time' => '16:00']
        ];
        $insertSchedule = $db->prepare("INSERT INTO schedules (route_id, departure_time) VALUES (?, ?)");
        foreach ($schedulesToSeed as $sc) {
            $insertSchedule->execute([$sc['route_id'], $sc['departure_time']]);
        }

        // Seed mock subscription for passenger (Monthly Student Pass, active)
        // Valid until 1 month from now
        $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));
        $db->prepare("INSERT INTO subscriptions (passenger_id, type, valid_until, status) VALUES (3, 'Monthly Student Pass', ?, 'Active')")->execute([$expiryDate]);

        // Seed transactions
        $transactionsToSeed = [
            ['passenger_id' => 3, 'amount' => 50.0, 'date' => date('Y-m-d H:i:s', strtotime('-10 days')), 'type' => 'Wallet Top-up'],
            ['passenger_id' => 3, 'amount' => -7.50, 'date' => date('Y-m-d H:i:s', strtotime('-8 days')), 'type' => 'Subscription Purchase']
        ];
        $insertTx = $db->prepare("INSERT INTO transactions (passenger_id, amount, date, type) VALUES (?, ?, ?, ?)");
        foreach ($transactionsToSeed as $tx) {
            $insertTx->execute([$tx['passenger_id'], $tx['amount'], $tx['date'], $tx['type']]);
        }

        // Seed an active shift for driver (Prof. Youcef Gharghout) on line 4 with Vehicle 1
        $db->prepare("INSERT INTO shifts (driver_id, vehicle_id, route_id, status, start_time, occupancy) VALUES (2, 1, 2, 'Active', ?, 78)")->execute([date('Y-m-d H:i:s', strtotime('-2 hours'))]);
    }

} catch (PDOException $e) {
    die("Database Connection / Initialization Failed: " . $e->getMessage());
}
