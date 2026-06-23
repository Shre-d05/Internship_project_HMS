<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$conn    = getDBConnection();
$msg     = '';
$msgType = 'success';

// ── ADD MESS ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_mess') {
    $name     = sanitize($conn, $_POST['name']);
    $hostelId = (int)$_POST['hostel_id'];
    $stmt = $conn->prepare("INSERT INTO mess (name, hostel_id) VALUES (?,?)");
    $stmt->bind_param("si", $name, $hostelId);
    $stmt->execute();
    $msg = "Mess '{$name}' added!";
}

// ── DELETE MESS ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_mess') {
    $messId = (int)$_POST['mess_id'];
    $conn->query("DELETE FROM mess_menu WHERE mess_id=$messId");
    $conn->query("DELETE FROM mess WHERE mess_id=$messId");
    $msg = "Mess deleted."; $msgType = 'error';
}

// ── SAVE/UPDATE MENU ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_menu') {
    $messId = (int)$_POST['mess_id'];
    $days   = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

    foreach ($days as $day) {
        $breakfast = sanitize($conn, $_POST["breakfast_$day"] ?? '');
        $lunch     = sanitize($conn, $_POST["lunch_$day"] ?? '');
        $dinner    = sanitize($conn, $_POST["dinner_$day"] ?? '');

        // Check if row exists
        $exists = $conn->query("SELECT menu_id FROM mess_menu WHERE mess_id=$messId AND day='$day'")->num_rows;
        if ($exists) {
            $conn->query("UPDATE mess_menu SET breakfast='$breakfast', lunch='$lunch', dinner='$dinner' WHERE mess_id=$messId AND day='$day'");
        } else {
            $conn->query("INSERT INTO mess_menu (mess_id,day,breakfast,lunch,dinner) VALUES ($messId,'$day','$breakfast','$lunch','$dinner')");
        }
    }
    $msg = "Weekly menu saved successfully!";
}

// ── FETCH ─────────────────────────────────────────────────────
$selectedMessId = (int)($_GET['mess_id'] ?? 0);
$messList = $conn->query("
    SELECT m.*, h.name AS hostel_name
    FROM mess m JOIN hostel h ON m.hostel_id=h.hostel_id
    ORDER BY h.name, m.name
")->fetch_all(MYSQLI_ASSOC);

if (!$selectedMessId && !empty($messList)) {
    $selectedMessId = $messList[0]['mess_id'];
}

// If a mess was just added/deleted redirect, use first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$selectedMessId && !empty($messList)) {
    $selectedMessId = $messList[0]['mess_id'];
}

$menuRows = [];
if ($selectedMessId) {
    $res = $conn->query("SELECT * FROM mess_menu WHERE mess_id=$selectedMessId ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
    while ($row = $res->fetch_assoc()) $menuRows[$row['day']] = $row;
}

$hostels = $conn->query("SELECT * FROM hostel ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$currentPage = 'mess';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Mess & Menu</h1>
            <p>Manage mess facilities and weekly meal schedules.</p>
        </div>
    </div>
    <div class="topbar-right">
        <button class="btn btn-primary" onclick="openModal('addMessModal')">Add Mess</button>
        <span class="topbar-badge">Admin</span>
    </div>
</div>

<div class="page-body">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:16px;">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Mess Facilities -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Mess Facilities</div>
    </div>
    <div class="card-body">
        <?php if (!empty($messList)): ?>
        <div style="padding:8px 0;">
        <?php foreach ($messList as $m): ?>
            <a href="?mess_id=<?= $m['mess_id'] ?>" style="display:block;">
            <div class="mess-item <?= $selectedMessId == $m['mess_id'] ? 'mess-item-active' : '' ?>">
                <div>
                    <div class="fw-600"><?= htmlspecialchars($m['name']) ?></div>
                    <div class="text-xs text-muted"><?= htmlspecialchars($m['hostel_name']) ?></div>
                </div>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this mess and its entire menu?')">
                    <input type="hidden" name="action" value="delete_mess">
                    <input type="hidden" name="mess_id" value="<?= $m['mess_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" onclick="event.stopPropagation()">Delete</button>
                </form>
            </div>
            </a>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><h4>No mess added</h4><p>Click "Add Mess" to create one.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Weekly Menu Editor -->
<?php if ($selectedMessId): ?>
<?php $currentMess = array_filter($messList, fn($m) => $m['mess_id'] == $selectedMessId);
      $currentMess = array_values($currentMess)[0] ?? null; ?>
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <div>
            <div class="card-title">Weekly Menu</div>
            <div class="card-subtitle"><?= $currentMess ? htmlspecialchars($currentMess['name']) : '' ?></div>
        </div>
    </div>
    <div class="card-body-pad">
        <form method="POST">
            <input type="hidden" name="action" value="save_menu">
            <input type="hidden" name="mess_id" value="<?= $selectedMessId ?>">

            <?php $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
            foreach ($days as $day):
                $menu = $menuRows[$day] ?? [];
            ?>
            <div style="margin-bottom:16px;padding:14px 16px;background:var(--gray-100);border-radius:10px;">
                <div style="font-weight:700;color:var(--primary-dark);margin-bottom:10px;font-size:0.85rem;"><?= $day ?></div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
                    <div>
                        <label class="form-label" style="font-size:0.65rem;">Breakfast</label>
                        <input type="text" name="breakfast_<?= $day ?>" class="form-control" style="font-size:0.82rem;padding:8px 10px;height:40px;"
                               value="<?= htmlspecialchars($menu['breakfast'] ?? '') ?>" placeholder="e.g. Poha, Tea">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:0.65rem;">Lunch</label>
                        <input type="text" name="lunch_<?= $day ?>" class="form-control" style="font-size:0.82rem;padding:8px 10px;height:40px;"
                               value="<?= htmlspecialchars($menu['lunch'] ?? '') ?>" placeholder="e.g. Dal, Rice, Roti">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:0.65rem;">Dinner</label>
                        <input type="text" name="dinner_<?= $day ?>" class="form-control" style="font-size:0.82rem;padding:8px 10px;height:40px;"
                               value="<?= htmlspecialchars($menu['dinner'] ?? '') ?>" placeholder="e.g. Paneer, Rice, Roti">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary" style="width:100%;">Save Weekly Menu</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-top:20px;">
    <div class="empty-state" style="padding:60px 24px;">
        <h4>Select a mess</h4>
        <p>Choose a mess from the list above to manage its weekly menu.</p>
    </div>
</div>
<?php endif; ?>
</div><!-- .page-body -->

<!-- ADD MESS MODAL -->
<div class="modal-overlay" id="addMessModal">
    <div class="modal" style="max-width:400px;">
        <div class="modal-header">
            <h4>Add New Mess</h4>
            <button class="modal-close" onclick="closeModal('addMessModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_mess">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Mess Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Ganga Mess">
                </div>
                <div class="form-group">
                    <label class="form-label">Hostel *</label>
                    <select name="hostel_id" class="form-control" required>
                        <option value="">Select Hostel</option>
                        <?php foreach ($hostels as $h): ?>
                        <option value="<?= $h['hostel_id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addMessModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Mess</button>
            </div>
        </form>
    </div>
</div>

<style>
.mess-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:12px 16px; border-radius:8px; margin-bottom:4px;
    cursor:pointer; transition:background 0.15s;
    border:1.5px solid transparent;
}
.mess-item:hover { background:var(--primary-light); border-color:var(--primary); }
.mess-item-active { background:var(--primary-light); border-color:var(--primary); }
</style>

<?php $conn->close(); include 'footer.php'; ?>
