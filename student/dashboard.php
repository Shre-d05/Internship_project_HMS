<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireStudent();

$conn      = getDBConnection();
$studentId = $_SESSION['student_id'];

// ── Fetch student full profile ────────────────────────────────
$student = $conn->query("
    SELECT s.*, r.room_number, r.room_type, r.capacity, r.status AS room_status,
           h.name AS hostel_name, h.address AS hostel_address
    FROM student s
    LEFT JOIN room r ON s.room_id = r.room_id
    LEFT JOIN hostel h ON r.hostel_id = h.hostel_id
    WHERE s.student_id = $studentId
")->fetch_assoc();

// ── Fetch complaint counts ────────────────────────────────────
$cmpTotal    = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId")->fetch_assoc()['c'];
$cmpPending  = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId AND status='pending'")->fetch_assoc()['c'];
$cmpResolved = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId AND status='resolved'")->fetch_assoc()['c'];

// ── Recent complaints ─────────────────────────────────────────
$recentComplaints = $conn->query("
    SELECT * FROM complaint WHERE student_id=$studentId ORDER BY created_at DESC LIMIT 3
");

// ── Today's mess menu ─────────────────────────────────────────
$today = date('l'); // Monday, Tuesday ...
$todayMenu = null;
if ($student['room_id']) {
    $todayMenu = $conn->query("
        SELECT mm.* FROM mess_menu mm
        JOIN mess m ON mm.mess_id = m.mess_id
        JOIN room r ON r.hostel_id = m.hostel_id
        WHERE r.room_id = {$student['room_id']} AND mm.day = '$today'
        LIMIT 1
    ")->fetch_assoc();
}

$conn->close();
$currentPage = 'dashboard';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Welcome, <?= htmlspecialchars(explode(' ', $student['name'])[0]) ?>!</h1>
            <p>Here's your hostel overview for today, <?= date('l, d F Y') ?>.</p>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-time" id="live-time"></span>
        <span class="topbar-badge">Student</span>
    </div>
</div>

<div class="page-body">

    <!-- ── STAT CARDS ── -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $student['room_number'] ?? '—' ?></div>
                <div class="stat-lbl">My Room</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num" style="font-size:1.1rem;line-height:1.4;"><?= $student['hostel_name'] ? htmlspecialchars($student['hostel_name']) : '—' ?></div>
                <div class="stat-lbl">My Hostel</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $cmpPending ?></div>
                <div class="stat-lbl">Pending Complaints</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $cmpResolved ?></div>
                <div class="stat-lbl">Resolved</div>
            </div>
        </div>
    </div>

    <div class="grid-2">

        <!-- ── My Room Card ── -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">My Room Details</div>
                <a href="room.php" class="btn btn-outline btn-sm">View More</a>
            </div>
            <div class="card-body-pad">
                <?php if ($student['room_number']): ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="info-block">
                        <div class="info-label">Room Number</div>
                        <div class="info-value"><?= htmlspecialchars($student['room_number']) ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Room Type</div>
                        <div class="info-value"><?= htmlspecialchars($student['room_type']) ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Hostel</div>
                        <div class="info-value"><?= htmlspecialchars($student['hostel_name']) ?></div>
                    </div>
                    <div class="info-block">
                        <div class="info-label">Capacity</div>
                        <div class="info-value"><?= $student['capacity'] ?> person(s)</div>
                    </div>
                    <div class="info-block" style="grid-column:1/-1;">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?= htmlspecialchars($student['hostel_address']) ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding:24px;">
                    <h4>No room assigned</h4>
                    <p>Contact admin to get a room allocated.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Today's Mess Menu ── -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Today's Mess Menu</div>
                <a href="mess_menu.php" class="btn btn-outline btn-sm">Full Week</a>
            </div>
            <div class="card-body-pad">
                <?php if ($todayMenu): ?>
                <div style="margin-bottom:8px;">
                    <span class="badge badge-primary" style="margin-bottom:12px;"><?= $today ?></span>
                </div>
                <div class="menu-meal-today">
                    <div class="meal-row">
                        <div>
                            <div class="meal-time">Breakfast</div>
                            <div class="meal-items"><?= htmlspecialchars($todayMenu['breakfast']) ?></div>
                        </div>
                    </div>
                    <div class="meal-row">
                        <div>
                            <div class="meal-time">Lunch</div>
                            <div class="meal-items"><?= htmlspecialchars($todayMenu['lunch']) ?></div>
                        </div>
                    </div>
                    <div class="meal-row" style="border-bottom:none;">
                        <div>
                            <div class="meal-time">Dinner</div>
                            <div class="meal-items"><?= htmlspecialchars($todayMenu['dinner']) ?></div>
                        </div>
                    </div>
                </div>
                <?php elseif ($student['room_id']): ?>
                <div class="empty-state" style="padding:24px;">
                <h4>Menu not updated</h4>
                    <p>Today's menu hasn't been added yet.</p>
                </div>
                <?php else: ?>
                <div class="empty-state" style="padding:24px;">
                <h4>No mess assigned</h4>
                    <p>Mess is assigned based on your room. Contact admin.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .grid-2 -->

    <!-- ── Recent Complaints ── -->
    <div class="card">
        <div class="card-header">
            <div><div class="card-title">My Recent Complaints</div><div class="card-subtitle"><?= $cmpTotal ?> total complaint(s)</div></div>
            <div class="flex gap-2">
                <a href="complaints.php" class="btn btn-outline btn-sm">View All</a>
                <a href="complaints.php#new" class="btn btn-primary btn-sm">New Complaint</a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($recentComplaints->num_rows > 0): ?>
            <table>
                <thead>
                    <tr><th>Category</th><th>Description</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php while ($c = $recentComplaints->fetch_assoc()):
                    $badge = match($c['status']) {
                        'pending'     => 'badge-warning',
                        'in_progress' => 'badge-info',
                        'resolved'    => 'badge-success',
                        default       => 'badge-gray'
                    };
                ?>
                    <tr>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($c['category']) ?></span></td>
                        <td style="max-width:280px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px;" title="<?= htmlspecialchars($c['description']) ?>">
                                <?= htmlspecialchars($c['description']) ?>
                            </div>
                        </td>
                        <td class="text-sm text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                        <td><span class="badge <?= $badge ?>"><?= ucwords(str_replace('_',' ',$c['status'])) ?></span></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <h4>No complaints yet</h4>
                <p>Submit a complaint if you face any issues.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- .page-body -->

<style>
.info-block { }
.menu-meal-today { display:flex; flex-direction:column; gap:0; }
.meal-row { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-light); }
.meal-time { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-secondary); }
.meal-items { font-size:0.88rem; color:var(--text); margin-top:2px; }
</style>

<?php include 'footer.php'; ?>
