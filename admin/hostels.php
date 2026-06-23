<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$conn    = getDBConnection();
$msg     = '';
$msgType = 'success';

// ── ADD ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name    = sanitize($conn, $_POST['name']);
    $address = sanitize($conn, $_POST['address']);
    $stmt = $conn->prepare("INSERT INTO hostel (name, address, total_rooms) VALUES (?,?,0)");
    $stmt->bind_param("ss", $name, $address);
    $stmt->execute();
    $msg = "Hostel '{$name}' added!";
}

// ── EDIT ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $hid     = (int)$_POST['hostel_id'];
    $name    = sanitize($conn, $_POST['name']);
    $address = sanitize($conn, $_POST['address']);
    $stmt = $conn->prepare("UPDATE hostel SET name=?, address=? WHERE hostel_id=?");
    $stmt->bind_param("ssi", $name, $address, $hid);
    $stmt->execute();
    $msg = "Hostel updated!";
}

// ── DELETE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $hid = (int)$_POST['hostel_id'];
    $hasRooms = $conn->query("SELECT room_id FROM room WHERE hostel_id=$hid")->num_rows;
    if ($hasRooms) {
        $msg = "Cannot delete: this hostel has rooms. Remove rooms first."; $msgType = 'error';
    } else {
        $conn->query("DELETE FROM mess WHERE hostel_id=$hid");
        $conn->query("DELETE FROM hostel WHERE hostel_id=$hid");
        $msg = "Hostel deleted."; $msgType = 'error';
    }
}

// ── FETCH ─────────────────────────────────────────────────────
$hostels = $conn->query("
    SELECT h.*,
           COUNT(DISTINCT r.room_id) AS room_count,
           COUNT(DISTINCT s.student_id) AS student_count,
           COUNT(DISTINCT m.mess_id) AS mess_count,
           SUM(CASE WHEN r.status='occupied' THEN 1 ELSE 0 END) AS occupied_count
    FROM hostel h
    LEFT JOIN room r ON h.hostel_id=r.hostel_id
    LEFT JOIN student s ON s.room_id=r.room_id
    LEFT JOIN mess m ON m.hostel_id=h.hostel_id
    GROUP BY h.hostel_id
    ORDER BY h.name
");

$currentPage = 'hostels';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Hostels</h1>
            <p>Manage hostel blocks and facilities.</p>
        </div>
    </div>
    <div class="topbar-right">
        <button class="btn btn-primary" onclick="openModal('addModal')">Add Hostel</button>
        <span class="topbar-badge">Admin</span>
    </div>
</div>

<div class="page-body">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:16px;">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<?php if ($hostels->num_rows > 0): ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;">
<?php while ($h = $hostels->fetch_assoc()):
    $occ = $h['room_count'] > 0 ? round(($h['occupied_count'] / $h['room_count']) * 100) : 0;
?>
<div class="card" style="margin-bottom:0;">
    <div style="background:linear-gradient(135deg,var(--primary),var(--accent));padding:20px 24px 16px;color:white;">
        <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($h['name']) ?></div>
        <div style="font-size:0.78rem;opacity:0.8;margin-top:3px;"><?= htmlspecialchars($h['address']) ?></div>
    </div>
    <div class="card-body-pad">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px;text-align:center;">
            <div style="background:var(--gray-100);border-radius:8px;padding:10px;">
                <div style="font-size:1.3rem;font-weight:700;color:var(--dark);"><?= $h['room_count'] ?></div>
                <div class="text-xs text-muted">Rooms</div>
            </div>
            <div style="background:var(--gray-100);border-radius:8px;padding:10px;">
                <div style="font-size:1.3rem;font-weight:700;color:var(--dark);"><?= $h['student_count'] ?></div>
                <div class="text-xs text-muted">Students</div>
            </div>
            <div style="background:var(--gray-100);border-radius:8px;padding:10px;">
                <div style="font-size:1.3rem;font-weight:700;color:var(--dark);"><?= $h['mess_count'] ?></div>
                <div class="text-xs text-muted">Mess</div>
            </div>
        </div>

        <!-- Occupancy bar -->
        <div style="margin-bottom:16px;">
            <div class="flex justify-between" style="margin-bottom:5px;">
                <span class="text-xs fw-600 text-muted">OCCUPANCY</span>
                <span class="text-xs fw-600" style="color:var(--primary);"><?= $occ ?>%</span>
            </div>
            <div style="height:8px;background:var(--gray-100);border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:<?= $occ ?>%;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:99px;transition:width 0.6s;"></div>
            </div>
        </div>

        <div class="flex gap-2">
            <button class="btn btn-outline btn-sm" style="flex:1;" onclick='openEditModal(<?= json_encode($h) ?>)'>Edit</button>
            <a href="rooms.php?hostel_id=<?= $h['hostel_id'] ?>" class="btn btn-primary btn-sm" style="flex:1;">Rooms</a>
            <form method="POST" onsubmit="return confirm('Delete this hostel?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="hostel_id" value="<?= $h['hostel_id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php else: ?>
<div class="card"><div class="empty-state"><h4>No hostels yet</h4><p>Add the first hostel to get started.</p></div></div>
<?php endif; ?>

</div><!-- .page-body -->

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h4>Add New Hostel</h4>
            <button class="modal-close" onclick="closeModal('addModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Hostel Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Saraswati Hostel">
                </div>
                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <input type="text" name="address" class="form-control" required placeholder="e.g. Block C, North Campus">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Hostel</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h4>Edit Hostel</h4>
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="hostel_id" id="edit_hostel_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Hostel Name *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <input type="text" name="address" id="edit_address" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(h) {
    document.getElementById('edit_hostel_id').value = h.hostel_id;
    document.getElementById('edit_name').value      = h.name;
    document.getElementById('edit_address').value   = h.address;
    openModal('editModal');
}
</script>

<?php $conn->close(); include 'footer.php'; ?>
