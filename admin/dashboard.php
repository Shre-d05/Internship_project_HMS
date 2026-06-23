<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$conn = getDBConnection();

// ── Fetch live stats ──────────────────────────────────────────
$totalStudents    = $conn->query("SELECT COUNT(*) AS c FROM student")->fetch_assoc()['c'];
$totalRooms       = $conn->query("SELECT COUNT(*) AS c FROM room")->fetch_assoc()['c'];
$occupiedRooms    = $conn->query("SELECT COUNT(*) AS c FROM room WHERE status='occupied'")->fetch_assoc()['c'];
$availableRooms   = $conn->query("SELECT COUNT(*) AS c FROM room WHERE status='available'")->fetch_assoc()['c'];
$pendingComplaints= $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE status='pending'")->fetch_assoc()['c'];
$totalHostels     = $conn->query("SELECT COUNT(*) AS c FROM hostel")->fetch_assoc()['c'];

// ── Recent students ───────────────────────────────────────────
$recentStudents = $conn->query("
    SELECT s.name, s.roll_no, s.department, s.year,
           r.room_number, h.name AS hostel_name
    FROM student s
    LEFT JOIN room r ON s.room_id = r.room_id
    LEFT JOIN hostel h ON r.hostel_id = h.hostel_id
    ORDER BY s.student_id DESC
    LIMIT 5
");

// ── Recent complaints ─────────────────────────────────────────
$recentComplaints = $conn->query("
    SELECT c.complaint_id, c.category, c.description,
           c.status, c.created_at, s.name AS student_name
    FROM complaint c
    JOIN student s ON c.student_id = s.student_id
    ORDER BY c.created_at DESC
    LIMIT 5
");

// ── Room status breakdown ─────────────────────────────────────
$roomStats = $conn->query("
    SELECT status, COUNT(*) AS cnt FROM room GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

$conn->close();

$currentPage = 'dashboard';
include 'header.php';
?>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Here's what's happening today.</p>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-time" id="live-time"></span>
        <span class="topbar-badge">Admin</span>
    </div>
</div>

<div class="page-body">

    <!-- ── STAT CARDS ── -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $totalStudents ?></div>
                <div class="stat-lbl">Total Students</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $totalRooms ?></div>
                <div class="stat-lbl">Total Rooms</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $occupiedRooms ?></div>
                <div class="stat-lbl">Occupied Rooms</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $pendingComplaints ?></div>
                <div class="stat-lbl">Pending Complaints</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $totalHostels ?></div>
                <div class="stat-lbl">Hostels</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-data">
                <div class="stat-num"><?= $availableRooms ?></div>
                <div class="stat-lbl">Available Rooms</div>
            </div>
        </div>
    </div>

    <!-- ── OCCUPANCY BAR ── -->
    <?php
    $occupancyPct = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
    ?>
    <div class="card mb-6">
        <div class="card-header">
            <div>
                <div class="card-title">Room Occupancy Overview</div>
                <div class="card-subtitle"><?= $occupiedRooms ?> of <?= $totalRooms ?> rooms occupied</div>
            </div>
            <span class="badge badge-primary"><?= $occupancyPct ?>% Full</span>
        </div>
        <div class="card-body-pad">
            <div class="occupancy-bars">
                <?php foreach ($roomStats as $rs):
                    $pct = $totalRooms > 0 ? round(($rs['cnt'] / $totalRooms) * 100) : 0;
                    $color = match($rs['status']) {
                        'occupied'    => 'var(--primary)',
                        'available'   => 'var(--success)',
                        'maintenance' => 'var(--warning)',
                        default       => 'var(--gray-300)'
                    };
                    $label = ucfirst($rs['status']);
                ?>
                <div style="margin-bottom:14px;">
                    <div class="flex justify-between mb-1" style="margin-bottom:6px;">
                        <span class="text-sm fw-600" style="color:var(--gray-700);"><?= $label ?></span>
                        <span class="text-sm text-muted"><?= $rs['cnt'] ?> rooms (<?= $pct ?>%)</span>
                    </div>
                    <div style="height:10px;background:var(--gray-100);border-radius:99px;overflow:hidden;">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:99px;transition:width 0.8s ease;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── TWO-COLUMN ROW ── -->
    <div class="grid-2">

        <!-- Recent Students -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Recent Students</div>
                    <div class="card-subtitle">Latest registrations</div>
                </div>
                <a href="students.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recentStudents->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll No.</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($s = $recentStudents->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                                <div class="text-xs text-muted"><?= htmlspecialchars($s['department']) ?> · Year <?= $s['year'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($s['roll_no']) ?></td>
                            <td><?= $s['room_number'] ? htmlspecialchars($s['room_number']) . '<br><span class="text-xs text-muted">' . htmlspecialchars($s['hostel_name']) . '</span>' : '<span class="text-muted">Unassigned</span>' ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state"><h4>No students yet</h4><p>Add students to get started.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Complaints -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Recent Complaints</div>
                    <div class="card-subtitle">Needs your attention</div>
                </div>
                <a href="complaints.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recentComplaints->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($c = $recentComplaints->fetch_assoc()):
                        $badgeCls = match($c['status']) {
                            'pending'    => 'badge-warning',
                            'resolved'   => 'badge-success',
                            'in_progress'=> 'badge-info',
                            default      => 'badge-gray'
                        };
                    ?>
                        <tr>
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($c['student_name']) ?></div>
                                <div class="text-xs text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></div>
                            </td>
                            <td><?= htmlspecialchars($c['category']) ?></td>
                            <td><span class="badge <?= $badgeCls ?>"><?= ucfirst($c['status']) ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state"><h4>No complaints</h4><p>All clear!</p></div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .grid-2 -->

</div><!-- .page-body -->

<?php include 'footer.php'; ?>
