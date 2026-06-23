<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireStudent();

$conn      = getDBConnection();
$studentId = $_SESSION['student_id'];

$student = $conn->query("
    SELECT s.*, r.room_number, r.room_type, r.capacity, r.status AS room_status,
           h.name AS hostel_name, h.address AS hostel_address, h.hostel_id,
           r.room_id
    FROM student s
    LEFT JOIN room r ON s.room_id = r.room_id
    LEFT JOIN hostel h ON r.hostel_id = h.hostel_id
    WHERE s.student_id = $studentId
")->fetch_assoc();

// Roommates (same room, different student)
$roommates = [];
if ($student['room_id']) {
    $res = $conn->query("
        SELECT name, roll_no, department, year, phone
        FROM student
        WHERE room_id = {$student['room_id']} AND student_id != $studentId
    ");
    while ($rm = $res->fetch_assoc()) $roommates[] = $rm;
}

// Mess for this hostel
$mess = null;
if ($student['hostel_id'] ?? false) {
    $mess = $conn->query("SELECT * FROM mess WHERE hostel_id={$student['hostel_id']} LIMIT 1")->fetch_assoc();
}

$conn->close();
$currentPage = 'room';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>My Room</h1>
            <p>Details about your accommodation and roommates.</p>
        </div>
    </div>
    <div class="topbar-right"><span class="topbar-badge">Student</span></div>
</div>

<div class="page-body">

<?php if ($student['room_number']): ?>

<div class="grid-2" style="align-items:start;">

    <!-- Room Details -->
    <div class="card">
        <div style="background:linear-gradient(135deg,var(--primary),var(--accent));padding:24px;color:white;">
            <div style="font-family:var(--font-display);font-size:2rem;font-weight:800;line-height:1;">Room <?= htmlspecialchars($student['room_number']) ?></div>
            <div style="font-size:0.85rem;opacity:0.8;margin-top:6px;"><?= htmlspecialchars($student['hostel_name']) ?></div>
        </div>
        <div class="card-body-pad">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <?php $details = [
                    ['label'=>'Hostel',   'val'=>$student['hostel_name']],
                    ['label'=>'Room Type', 'val'=>$student['room_type']],
                    ['label'=>'Capacity',  'val'=>$student['capacity'] . ' person(s)'],
                    ['label'=>'Address',   'val'=>$student['hostel_address']],
                ];
                foreach ($details as $d): ?>
                <div style="background:var(--gray-100);border-radius:10px;padding:14px;">
                    <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:0.4px;margin-bottom:3px;"><?= $d['label'] ?></div>
                    <div class="fw-600"><?= htmlspecialchars($d['val']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($mess): ?>
            <div style="margin-top:16px;padding:14px;background:var(--primary-light);border-radius:10px;border-left:4px solid var(--primary);">
                <div class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:0.4px;margin-bottom:4px;">Mess Facility</div>
                <div class="fw-600" style="color:var(--primary-dark);"><?= htmlspecialchars($mess['name']) ?></div>
            </div>
            <?php endif; ?>

            <div style="margin-top:16px;">
                <a href="complaints.php#new" class="btn btn-primary" style="width:100%;justify-content:center;">
                    Report a Room Issue
                </a>
            </div>
        </div>
    </div>

    <!-- Roommates -->
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Roommates</div>
                <div class="card-subtitle"><?= count($roommates) ?> other student(s) in this room</div>
            </div>
        </div>
        <div class="card-body-pad">
            <?php if (!empty($roommates)): ?>
            <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($roommates as $rm): ?>
                <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--gray-100);border-radius:10px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-weight:700;color:white;flex-shrink:0;">
                        <?= strtoupper(substr($rm['name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-600"><?= htmlspecialchars($rm['name']) ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($rm['roll_no']) ?> · <?= htmlspecialchars($rm['department']) ?> · Year <?= $rm['year'] ?></div>
                        <div class="text-xs" style="color:var(--accent);margin-top:2px;"><?= htmlspecialchars($rm['phone']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:32px;">
                <h4>No roommates</h4>
                <p>You have this room to yourself!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- .grid-2 -->

<?php else: ?>
<div class="card">
    <div class="empty-state" style="padding:80px 24px;">
        <h4>No room assigned yet</h4>
        <p>Please contact the hostel administrator to get a room allocated.</p>
    </div>
</div>
<?php endif; ?>

</div><!-- .page-body -->
<?php include 'footer.php'; ?>
