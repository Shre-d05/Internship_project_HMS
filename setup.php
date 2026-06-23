<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS — Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #EEF2FF; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; border-radius: 16px; box-shadow: 0 8px 40px rgba(79,70,229,0.15); padding: 40px; max-width: 600px; width: 95%; }
        h1 { font-size: 1.5rem; color: #111827; margin-bottom: 6px; }
        p  { color: #6B7280; font-size: 0.9rem; margin-bottom: 24px; }
        .step { padding: 14px 18px; border-radius: 10px; margin-bottom: 12px; font-size: 0.88rem; display: flex; align-items: flex-start; gap: 12px; }
        .step.ok   { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }
        .step.fail { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
        .step.info { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
        .icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .creds { background: #F3F4F6; border-radius: 10px; padding: 18px; margin: 20px 0; }
        .creds h3 { font-size: 0.85rem; color: #374151; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .cred-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #E5E7EB; font-size: 0.85rem; }
        .cred-row:last-child { border-bottom: none; }
        .cred-label { color: #6B7280; }
        .cred-val   { font-weight: 700; color: #111827; font-family: monospace; background: #E5E7EB; padding: 2px 8px; border-radius: 4px; }
        .btn { display: inline-block; padding: 12px 28px; background: #4F46E5; color: white; border-radius: 8px; font-weight: 600; font-size: 0.9rem; text-decoration: none; margin-top: 16px; }
        .btn:hover { background: #3730A3; }
        .warn { background: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; border-radius: 8px; padding: 12px 16px; font-size: 0.82rem; margin-top: 16px; }
        code { background: #F3F4F6; padding: 1px 6px; border-radius: 4px; font-family: monospace; font-size: 0.85em; }
    </style>
</head>
<body>
<div class="container">
    <h1>🏛️ HMS — First-Time Setup</h1>
    <p>This script sets up your database with correct passwords. Run it once, then delete it.</p>

    <?php
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'hostel_management');

    $steps = [];
    $allOk = true;

    // ── Step 1: Connect ──────────────────────────────────────
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        $steps[] = ['fail', '❌', 'Database connection failed: ' . $conn->connect_error .
            '. Make sure XAMPP MySQL is running and database <code>hostel_management</code> exists.'];
        $allOk = false;
    } else {
        $steps[] = ['ok', '✅', 'Connected to MySQL successfully.'];
        $conn->set_charset('utf8mb4');

        // ── Step 2: Check tables ─────────────────────────────
        $tables = ['users','hostel','room','student','complaint','mess','mess_menu'];
        $missing = [];
        foreach ($tables as $t) {
            $r = $conn->query("SHOW TABLES LIKE '$t'");
            if ($r->num_rows === 0) $missing[] = $t;
        }
        if (!empty($missing)) {
            $steps[] = ['fail', '❌', 'Missing tables: ' . implode(', ', $missing) .
                '. Please import <code>database.sql</code> in phpMyAdmin first, then re-run this page.'];
            $allOk = false;
        } else {
            $steps[] = ['ok', '✅', 'All required tables found.'];

            // ── Step 3: Fix / Create admin user ──────────────
            $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
            $existing  = $conn->query("SELECT user_id FROM users WHERE username='admin'")->fetch_assoc();
            if ($existing) {
                $stmt = $conn->prepare("UPDATE users SET password=?, role='admin' WHERE username='admin'");
                $stmt->bind_param("s", $adminHash);
                $stmt->execute();
                $steps[] = ['ok', '✅', 'Admin user password updated (admin / admin123).'];
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
                $stmt->bind_param("s", $adminHash);
                $stmt->execute();
                $steps[] = ['ok', '✅', 'Admin user created (admin / admin123).'];
            }

            // ── Step 4: Fix student users ─────────────────────
            $stuHash = password_hash('student123', PASSWORD_BCRYPT);

            // Get all students who have user accounts
            $stuRes = $conn->query("
                SELECT s.student_id, s.roll_no, s.name, s.user_id
                FROM student s
            ");
            $stuCount = 0;
            while ($stu = $stuRes->fetch_assoc()) {
                if ($stu['user_id']) {
                    // Update existing user password
                    $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
                    $stmt->bind_param("si", $stuHash, $stu['user_id']);
                    $stmt->execute();
                } else {
                    // Create user account for this student
                    $roll = $stu['roll_no'];
                    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                    $stmt->bind_param("ss", $roll, $stuHash);
                    $stmt->execute();
                    $newUid = $conn->insert_id;
                    $stmt2  = $conn->prepare("UPDATE student SET user_id=? WHERE student_id=?");
                    $stmt2->bind_param("ii", $newUid, $stu['student_id']);
                    $stmt2->execute();
                }
                $stuCount++;
            }

            // Create demo students if none exist
            if ($stuCount === 0) {
                // Make sure hostels & rooms exist first
                $hostels = $conn->query("SELECT hostel_id FROM hostel")->num_rows;
                if ($hostels === 0) {
                    $conn->query("INSERT INTO hostel (name, address, total_rooms) VALUES ('Ganga Hostel','Block A, Campus Road',50),('Yamuna Hostel','Block B, Campus Road',40)");
                    $conn->query("INSERT INTO room (hostel_id,room_number,room_type,capacity,status) VALUES (1,'101','Single',1,'available'),(1,'102','Double',2,'occupied'),(1,'103','Triple',3,'available'),(1,'104','Double',2,'occupied'),(2,'201','Double',2,'available'),(2,'202','Triple',3,'occupied')");
                    $conn->query("INSERT INTO mess (name,hostel_id) VALUES ('Ganga Mess',1),('Yamuna Mess',2)");
                    $steps[] = ['info', 'ℹ️', 'Demo hostels, rooms and mess created.'];
                }
                // Create demo students
                foreach ([
                    ['2021CS001','Rahul Sharma','Computer Science',3,'9876543210','rahul@student.edu',2],
                    ['2021CS002','Priya Singh','Computer Science',2,'9876543211','priya@student.edu',4],
                ] as [$roll, $name, $dept, $year, $phone, $email, $roomId]) {
                    $existUser = $conn->query("SELECT user_id FROM users WHERE username='$roll'")->fetch_assoc();
                    if ($existUser) {
                        $uid = $existUser['user_id'];
                        $conn->query("UPDATE users SET password='$stuHash' WHERE user_id=$uid");
                    } else {
                        $stmt = $conn->prepare("INSERT INTO users (username,password,role) VALUES (?,?,'student')");
                        $stmt->bind_param("ss", $roll, $stuHash);
                        $stmt->execute();
                        $uid = $conn->insert_id;
                    }
                    $existStu = $conn->query("SELECT student_id FROM student WHERE roll_no='$roll'")->fetch_assoc();
                    if (!$existStu) {
                        $stmt = $conn->prepare("INSERT INTO student (user_id,name,roll_no,department,year,phone,email,room_id) VALUES (?,?,?,?,?,?,?,?)");
                        $stmt->bind_param("isssisis", $uid, $name, $roll, $dept, $year, $phone, $email, $roomId);
                        $stmt->execute();
                        // Update room status
                        $conn->query("UPDATE room SET status='occupied' WHERE room_id=$roomId");
                        // Sync session data
                        $conn->query("UPDATE student SET user_id=$uid WHERE roll_no='$roll'");
                    }
                    $stuCount++;
                }
                $steps[] = ['ok', '✅', "Created $stuCount demo student accounts."];
            } else {
                $steps[] = ['ok', '✅', "Updated passwords for $stuCount student account(s)."];
            }

            // ── Step 5: Demo complaints & menu ────────────────
            $cmpCount = $conn->query("SELECT COUNT(*) AS c FROM complaint")->fetch_assoc()['c'];
            if ($cmpCount == 0) {
                $conn->query("INSERT INTO complaint (student_id,category,description,status) VALUES (1,'Maintenance','Water tap leaking in room','pending'),(1,'Electricity','Light not working in corridor','resolved'),(2,'Mess','Food quality needs improvement','pending')");
                $steps[] = ['info', 'ℹ️', 'Demo complaints inserted.'];
            }
            $menuCount = $conn->query("SELECT COUNT(*) AS c FROM mess_menu")->fetch_assoc()['c'];
            if ($menuCount == 0) {
                $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                $menus = [
                    ['Poha, Tea, Banana',    'Dal, Rice, Sabzi, Roti',       'Paneer Curry, Rice, Roti'],
                    ['Idli, Sambar',          'Rajma, Rice, Roti',            'Aloo Curry, Dal, Roti'],
                    ['Paratha, Curd',         'Chole, Rice, Roti',            'Mix Veg, Dal, Roti'],
                    ['Upma, Tea, Fruit',      'Dal Makhani, Rice, Roti',      'Kadai Paneer, Rice'],
                    ['Bread, Butter, Egg',    'Palak Dal, Rice, Roti',        'Dum Aloo, Roti, Rice'],
                    ['Puri, Sabzi, Tea',      'Special Biryani, Raita',       'Dal Fry, Roti, Kheer'],
                    ['Dosa, Chutney, Sambar', 'Special Lunch, Sweet',         'Special Dinner, Dessert'],
                ];
                $messIds = $conn->query("SELECT mess_id FROM mess LIMIT 2")->fetch_all(MYSQLI_ASSOC);
                foreach ($messIds as $mid) {
                    foreach ($days as $i => $day) {
                        $b = $conn->real_escape_string($menus[$i][0]);
                        $l = $conn->real_escape_string($menus[$i][1]);
                        $d = $conn->real_escape_string($menus[$i][2]);
                        $conn->query("INSERT INTO mess_menu (mess_id,day,breakfast,lunch,dinner) VALUES ({$mid['mess_id']},'$day','$b','$l','$d')");
                    }
                }
                $steps[] = ['info', 'ℹ️', 'Demo mess menu inserted.'];
            }

            $steps[] = ['ok', '✅', 'Setup complete! You can now log in.'];
        }
        $conn->close();
    }

    // ── Render steps ──────────────────────────────────────────
    foreach ($steps as [$type, $icon, $msg]) {
        echo "<div class='step $type'><span class='icon'>$icon</span><span>$msg</span></div>";
    }
    ?>

    <?php if ($allOk): ?>
    <div class="creds">
        <h3>🔑 Login Credentials</h3>
        <div class="cred-row">
            <span class="cred-label">👨‍💼 Admin Username</span>
            <span class="cred-val">admin</span>
        </div>
        <div class="cred-row">
            <span class="cred-label">👨‍💼 Admin Password</span>
            <span class="cred-val">admin123</span>
        </div>
        <div class="cred-row">
            <span class="cred-label">👨‍🎓 Student Username</span>
            <span class="cred-val">2021CS001</span>
        </div>
        <div class="cred-row">
            <span class="cred-label">👨‍🎓 Student Password</span>
            <span class="cred-val">student123</span>
        </div>
    </div>
    <a href="index.php" class="btn">🚀 Go to Login Page</a>
    <div class="warn">⚠️ <strong>Security:</strong> Delete <code>setup.php</code> from your server after logging in successfully.</div>
    <?php else: ?>
    <div class="warn">
        ⚠️ Fix the errors above, then <a href="setup.php" style="color:#92400E;font-weight:600;">refresh this page</a> to re-run setup.<br><br>
        <strong>Common fix:</strong> If tables are missing, open <strong>phpMyAdmin</strong> → select <code>hostel_management</code> database → click <strong>Import</strong> → choose <code>database.sql</code> → click Go. Then refresh this page.
    </div>
    <?php endif; ?>
</div>
</body>
</html>
