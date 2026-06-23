<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireStudent();

$conn      = getDBConnection();
$studentId = $_SESSION['student_id'];

// Get student's hostel via room
$student = $conn->query("
    SELECT s.room_id, r.hostel_id, h.name AS hostel_name
    FROM student s
    LEFT JOIN room r ON s.room_id=r.room_id
    LEFT JOIN hostel h ON r.hostel_id=h.hostel_id
    WHERE s.student_id=$studentId
")->fetch_assoc();

$messList     = [];
$selectedMess = null;
$menuByDay    = [];

if ($student['hostel_id']) {
    $res = $conn->query("SELECT * FROM mess WHERE hostel_id={$student['hostel_id']} ORDER BY mess_id");
    while ($m = $res->fetch_assoc()) $messList[] = $m;
}

// If no room/hostel, show all mess
if (empty($messList)) {
    $res = $conn->query("
        SELECT m.*, h.name AS hostel_name FROM mess m
        JOIN hostel h ON m.hostel_id=h.hostel_id ORDER BY h.name, m.name
    ");
    while ($m = $res->fetch_assoc()) $messList[] = $m;
}

$selectedMessId = (int)($_GET['mess_id'] ?? ($messList[0]['mess_id'] ?? 0));
foreach ($messList as $m) { if ($m['mess_id'] == $selectedMessId) { $selectedMess = $m; break; } }

if ($selectedMessId) {
    $res = $conn->query("SELECT * FROM mess_menu WHERE mess_id=$selectedMessId ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
    while ($row = $res->fetch_assoc()) $menuByDay[$row['day']] = $row;
}

$conn->close();
$currentPage = 'mess_menu';
$today       = date('l');
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Mess Menu</h1>
            <p>Weekly meal schedule<?= $student['hostel_name'] ? ' — ' . htmlspecialchars($student['hostel_name']) : '' ?>. Today is <strong><?= $today ?></strong>.</p>
        </div>
    </div>
    <div class="topbar-right"><span class="topbar-badge">Student</span></div>
</div>

<div class="page-body">

<!-- Mess selector (if multiple) -->
<?php if (count($messList) > 1): ?>
<div class="flex gap-2" style="margin-bottom:20px;flex-wrap:wrap;">
    <?php foreach ($messList as $m): ?>
    <a href="?mess_id=<?= $m['mess_id'] ?>" class="btn btn-<?= $selectedMessId == $m['mess_id'] ? 'primary' : 'outline' ?>">
        <?= htmlspecialchars($m['name']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($menuByDay)): ?>

<!-- Today's highlight -->
<?php if (isset($menuByDay[$today])): $t = $menuByDay[$today]; ?>
<div class="card" style="margin-bottom:20px;border-left:4px solid var(--primary);">
    <div class="card-header" style="background:var(--primary-light);">
        <div>
            <div class="card-title" style="color:var(--primary-dark);">Today's Menu — <?= $today ?></div>
            <div class="card-subtitle"><?= $selectedMess ? htmlspecialchars($selectedMess['name']) : '' ?></div>
        </div>
        <span class="badge badge-primary">Today</span>
    </div>
    <div class="card-body-pad">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
            <?php foreach ([['Breakfast','breakfast'],['Lunch','lunch'],['Dinner','dinner']] as [$label, $key]): ?>
            <div style="background:var(--gray-100);border-radius:10px;padding:16px;">
                <div class="text-xs fw-600 text-muted" style="text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;"><?= $label ?></div>
                <div class="text-sm" style="color:var(--dark);line-height:1.6;"><?= htmlspecialchars($t[$key] ?: '—') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Full week grid -->
<div class="card">
    <div class="card-header">
        <div><div class="card-title">Full Week Menu</div><div class="card-subtitle"><?= $selectedMess ? htmlspecialchars($selectedMess['name']) : 'All Mess' ?></div></div>
    </div>
    <div class="card-body">
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Breakfast</th>
                    <th>Lunch</th>
                    <th>Dinner</th>
                </tr>
            </thead>
            <tbody>
            <?php $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            foreach ($days as $day):
                $m   = $menuByDay[$day] ?? null;
                $isT = $day === $today;
            ?>
                <tr <?= $isT ? 'style="background:var(--primary-light);"' : '' ?>>
                    <td>
                        <div class="fw-600" style="<?= $isT ? 'color:var(--primary-dark);' : '' ?>"><?= $day ?></div>
                        <?php if ($isT): ?><span class="badge badge-primary" style="margin-top:3px;">Today</span><?php endif; ?>
                    </td>
                    <td class="text-sm"><?= htmlspecialchars($m['breakfast'] ?? '—') ?></td>
                    <td class="text-sm"><?= htmlspecialchars($m['lunch'] ?? '—') ?></td>
                    <td class="text-sm"><?= htmlspecialchars($m['dinner'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php elseif (empty($messList)): ?>
<div class="card">
    <div class="empty-state" style="padding:80px;">
        <h4>No mess facility found</h4>
        <p>Contact admin to set up a mess for your hostel.</p>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="empty-state" style="padding:80px;">
        <h4>Menu not uploaded yet</h4>
        <p>The weekly menu for this mess hasn't been added yet. Check back later.</p>
    </div>
</div>
<?php endif; ?>

</div><!-- .page-body -->
<?php include 'footer.php'; ?>
