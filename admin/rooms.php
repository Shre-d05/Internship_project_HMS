<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$conn    = getDBConnection();
$msg     = '';
$msgType = 'success';

// ── ADD ROOM ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $hostelId   = (int)$_POST['hostel_id'];
    $roomNum    = sanitize($conn, $_POST['room_number']);
    $roomType   = sanitize($conn, $_POST['room_type']);
    $capacity   = (int)$_POST['capacity'];
    $status     = sanitize($conn, $_POST['status']);

    $stmt = $conn->prepare("INSERT INTO room (hostel_id,room_number,room_type,capacity,status) VALUES (?,?,?,?,?)");
    $stmt->bind_param("issis", $hostelId, $roomNum, $roomType, $capacity, $status);
    if ($stmt->execute()) {
        // Update hostel total_rooms count
        $conn->query("UPDATE hostel SET total_rooms = (SELECT COUNT(*) FROM room WHERE hostel_id=$hostelId) WHERE hostel_id=$hostelId");
        $msg = "Room {$roomNum} added successfully!";
    } else {
        $msg = "Error adding room."; $msgType = 'error';
    }
}

// ── EDIT ROOM ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $roomId   = (int)$_POST['room_id'];
    $roomNum  = sanitize($conn, $_POST['room_number']);
    $roomType = sanitize($conn, $_POST['room_type']);
    $capacity = (int)$_POST['capacity'];
    $status   = sanitize($conn, $_POST['status']);

    $stmt = $conn->prepare("UPDATE room SET room_number=?,room_type=?,capacity=?,status=? WHERE room_id=?");
    $stmt->bind_param("ssisi", $roomNum, $roomType, $capacity, $status, $roomId);
    $stmt->execute();
    $msg = "Room updated successfully!";
}

// ── DELETE ROOM ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $roomId = (int)$_POST['room_id'];
    $hasStudent = $conn->query("SELECT student_id FROM student WHERE room_id=$roomId")->num_rows;
    if ($hasStudent) {
        $msg = "Cannot delete: students are assigned to this room."; $msgType = 'error';
    } else {
        $conn->query("DELETE FROM room WHERE room_id=$roomId");
        $msg = "Room deleted."; $msgType = 'error';
    }
}

// ── FETCH ─────────────────────────────────────────────────────
$filterStatus  = sanitize($conn, $_GET['status'] ?? '');
$filterHostel  = (int)($_GET['hostel_id'] ?? 0);
$where = "WHERE 1=1";
if ($filterStatus) $where .= " AND r.status='$filterStatus'";
if ($filterHostel) $where .= " AND r.hostel_id=$filterHostel";

$rooms = $conn->query("
    SELECT r.*, h.name AS hostel_name,
           COUNT(s.student_id) AS assigned_count
    FROM room r
    JOIN hostel h ON r.hostel_id = h.hostel_id
    LEFT JOIN student s ON s.room_id = r.room_id
    $where
    GROUP BY r.room_id
    ORDER BY h.name, r.room_number
");

$hostels = $conn->query("SELECT * FROM hostel ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Summary counts
$counts = [];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM room GROUP BY status");
while ($row = $res->fetch_assoc()) $counts[$row['status']] = $row['cnt'];

$currentPage = 'rooms';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Rooms</h1>
            <p>Manage hostel rooms, types, and occupancy status.</p>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-badge">Admin</span>
    </div>
</div>

<div class="page-body">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:16px;">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Quick stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <?php
    $statItems = [
        ['label'=>'Total Rooms',   'key'=>null,          'cls'=>'blue'],
        ['label'=>'Available',     'key'=>'available',   'cls'=>'green'],
        ['label'=>'Occupied',      'key'=>'occupied',    'cls'=>'red'],
        ['label'=>'Maintenance',   'key'=>'maintenance', 'cls'=>'amber'],
    ];
    $total = array_sum($counts);
    foreach ($statItems as $si):
        $val = $si['key'] === null ? $total : ($counts[$si['key']] ?? 0);
    ?>
    <div class="stat-card">
        <div class="stat-data">
            <div class="stat-num"><?= $val ?></div>
            <div class="stat-lbl"><?= $si['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="page-actions">
    <form method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">
        <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="available"   <?= $filterStatus==='available'   ? 'selected':'' ?>>Available</option>
            <option value="occupied"    <?= $filterStatus==='occupied'    ? 'selected':'' ?>>Occupied</option>
            <option value="maintenance" <?= $filterStatus==='maintenance' ? 'selected':'' ?>>Maintenance</option>
        </select>
        <select name="hostel_id" class="form-control" style="width:180px;" onchange="this.form.submit()">
            <option value="">All Hostels</option>
            <?php foreach ($hostels as $h): ?>
            <option value="<?= $h['hostel_id'] ?>" <?= $filterHostel==$h['hostel_id'] ? 'selected':'' ?>><?= htmlspecialchars($h['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterStatus || $filterHostel): ?>
        <a href="rooms.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>
    <button class="btn btn-primary" onclick="openModal('addModal')">Add Room</button>
</div>

<div class="card">
    <div class="card-header">
        <div><div class="card-title">Room List</div><div class="card-subtitle"><?= $rooms->num_rows ?> room(s)</div></div>
    </div>
    <div class="card-body">
        <?php if ($rooms->num_rows > 0): ?>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Room No.</th>
                    <th>Hostel</th>
                    <th>Type</th>
                    <th>Capacity</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i=1; while ($r = $rooms->fetch_assoc()):
                $badge = match($r['status']) {
                    'available'   => 'badge-success',
                    'occupied'    => 'badge-danger',
                    'maintenance' => 'badge-warning',
                    default       => 'badge-gray'
                };
            ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td><span class="fw-600" style="font-size:1rem;"><?= htmlspecialchars($r['room_number']) ?></span></td>
                    <td><?= htmlspecialchars($r['hostel_name']) ?></td>
                    <td><?= htmlspecialchars($r['room_type']) ?></td>
                    <td><?= $r['capacity'] ?> person(s)</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="background:var(--primary);height:6px;border-radius:3px;width:<?= $r['capacity']>0 ? round(($r['assigned_count']/$r['capacity'])*50) : 0 ?>px;min-width:4px;"></div>
                            <?= $r['assigned_count'] ?>/<?= $r['capacity'] ?>
                        </div>
                    </td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($r) ?>)'>Edit</button>
                            <form method="POST" onsubmit="return confirm('Delete this room?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="room_id" value="<?= $r['room_id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state">

            <h4>No rooms found</h4>
            <p>Add rooms to start managing accommodation.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- .page-body -->

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Add New Room</h4>
            <button class="modal-close" onclick="closeModal('addModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Hostel *</label>
                        <select name="hostel_id" class="form-control" required>
                            <option value="">Select Hostel</option>
                            <?php foreach ($hostels as $h): ?>
                            <option value="<?= $h['hostel_id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Number *</label>
                        <input type="text" name="room_number" class="form-control" required placeholder="e.g. 201">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Type *</label>
                        <select name="room_type" class="form-control" required>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Dormitory">Dormitory</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capacity *</label>
                        <input type="number" name="capacity" class="form-control" required min="1" max="10" value="1">
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Room</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Edit Room</h4>
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Room Number *</label>
                        <input type="text" name="room_number" id="edit_room_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room Type *</label>
                        <select name="room_type" id="edit_room_type" class="form-control" required>
                            <option value="Single">Single</option>
                            <option value="Double">Double</option>
                            <option value="Triple">Triple</option>
                            <option value="Dormitory">Dormitory</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capacity *</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" required min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
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
function openEditModal(r) {
    document.getElementById('edit_room_id').value     = r.room_id;
    document.getElementById('edit_room_number').value = r.room_number;
    document.getElementById('edit_capacity').value    = r.capacity;
    setSelect('edit_room_type', r.room_type);
    setSelect('edit_status',    r.status);
    openModal('editModal');
}
function setSelect(id, val) {
    const sel = document.getElementById(id);
    for (let o of sel.options) { o.selected = o.value === val; }
}
</script>

<?php $conn->close(); include 'footer.php'; ?>
