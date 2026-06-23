<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$conn    = getDBConnection();
$msg     = '';
$msgType = 'success';

// ── UPDATE STATUS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $cid    = (int)$_POST['complaint_id'];
    $status = sanitize($conn, $_POST['status']);
    $stmt = $conn->prepare("UPDATE complaint SET status=? WHERE complaint_id=?");
    $stmt->bind_param("si", $status, $cid);
    $stmt->execute();
    $msg = "Complaint #$cid status updated to " . ucfirst($status) . ".";
}

// ── DELETE ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $cid = (int)$_POST['complaint_id'];
    $conn->query("DELETE FROM complaint WHERE complaint_id=$cid");
    $msg = "Complaint deleted."; $msgType = 'error';
}

// ── FILTER ────────────────────────────────────────────────────
$filterStatus   = sanitize($conn, $_GET['status'] ?? '');
$filterCategory = sanitize($conn, $_GET['category'] ?? '');
$where = "WHERE 1=1";
if ($filterStatus)   $where .= " AND c.status='$filterStatus'";
if ($filterCategory) $where .= " AND c.category='$filterCategory'";

$complaints = $conn->query("
    SELECT c.*, s.name AS student_name, s.roll_no,
           r.room_number, h.name AS hostel_name
    FROM complaint c
    JOIN student s ON c.student_id = s.student_id
    LEFT JOIN room r ON s.room_id = r.room_id
    LEFT JOIN hostel h ON r.hostel_id = h.hostel_id
    $where
    ORDER BY c.created_at DESC
");

// Count by status
$statusCounts = [];
$res = $conn->query("SELECT status, COUNT(*) as cnt FROM complaint GROUP BY status");
while ($row = $res->fetch_assoc()) $statusCounts[$row['status']] = $row['cnt'];

$categories = $conn->query("SELECT DISTINCT category FROM complaint ORDER BY category")->fetch_all(MYSQLI_ASSOC);

$currentPage = 'complaints';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Complaints</h1>
            <p>Track and resolve student complaints.</p>
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

<!-- Stat cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= array_sum($statusCounts) ?></div><div class="stat-lbl">Total</div></div></div>
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $statusCounts['pending'] ?? 0 ?></div><div class="stat-lbl">Pending</div></div></div>
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $statusCounts['in_progress'] ?? 0 ?></div><div class="stat-lbl">In Progress</div></div></div>
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $statusCounts['resolved'] ?? 0 ?></div><div class="stat-lbl">Resolved</div></div></div>
</div>

<!-- Filters -->
<div class="page-actions">
    <form method="GET" class="flex gap-2 items-center" style="flex-wrap:wrap;">
        <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending"     <?= $filterStatus==='pending'     ? 'selected':'' ?>>Pending</option>
            <option value="in_progress" <?= $filterStatus==='in_progress' ? 'selected':'' ?>>In Progress</option>
            <option value="resolved"    <?= $filterStatus==='resolved'    ? 'selected':'' ?>>Resolved</option>
        </select>
        <select name="category" class="form-control" style="width:180px;" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $filterCategory===$cat['category'] ? 'selected':'' ?>><?= htmlspecialchars($cat['category']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filterStatus || $filterCategory): ?>
        <a href="complaints.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div><div class="card-title">All Complaints</div><div class="card-subtitle"><?= $complaints->num_rows ?> record(s)</div></div>
    </div>
    <div class="card-body">
    <?php if ($complaints->num_rows > 0): ?>
    <div style="overflow-x:auto;">
    <table>
        <thead>
            <tr>
                <th>#ID</th>
                <th>Student</th>
                <th>Room</th>
                <th>Category</th>
                <th>Description</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($c = $complaints->fetch_assoc()):
            $badge = match($c['status']) {
                'pending'     => 'badge-warning',
                'in_progress' => 'badge-info',
                'resolved'    => 'badge-success',
                default       => 'badge-gray'
            };
        ?>
            <tr>
                <td><span class="text-muted">#<?= $c['complaint_id'] ?></span></td>
                <td>
                    <div class="fw-600"><?= htmlspecialchars($c['student_name']) ?></div>
                    <div class="text-xs text-muted"><?= htmlspecialchars($c['roll_no']) ?></div>
                </td>
                <td><?= $c['room_number'] ? 'Rm ' . htmlspecialchars($c['room_number']) : '—' ?></td>
                <td><span class="badge badge-primary"><?= htmlspecialchars($c['category']) ?></span></td>
                <td style="max-width:220px;">
                    <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;" title="<?= htmlspecialchars($c['description']) ?>">
                        <?= htmlspecialchars($c['description']) ?>
                    </div>
                </td>
                <td class="text-sm text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td><span class="badge <?= $badge ?>"><?= ucwords(str_replace('_',' ',$c['status'])) ?></span></td>
                <td>
                    <div class="flex gap-2" style="flex-wrap:nowrap;">
                        <button class="btn btn-outline btn-sm" onclick="openStatusModal(<?= $c['complaint_id'] ?>, '<?= $c['status'] ?>')">Update</button>
                        <form method="POST" onsubmit="return confirm('Delete this complaint?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
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

        <h4>No complaints found</h4>
        <p><?= ($filterStatus || $filterCategory) ? 'Try different filters.' : 'All clear — no complaints submitted yet.' ?></p>
    </div>
    <?php endif; ?>
    </div>
</div>

</div><!-- .page-body -->

<!-- STATUS UPDATE MODAL -->
<div class="modal-overlay" id="statusModal">
    <div class="modal" style="max-width:380px;">
        <div class="modal-header">
            <h4>Update Complaint Status</h4>
            <button class="modal-close" onclick="closeModal('statusModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="complaint_id" id="status_complaint_id">
            <div class="modal-body">
                <label class="form-label">New Status</label>
                <select name="status" id="status_select" class="form-control">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(id, currentStatus) {
    document.getElementById('status_complaint_id').value = id;
    const sel = document.getElementById('status_select');
    for (let o of sel.options) { o.selected = o.value === currentStatus; }
    openModal('statusModal');
}
</script>

<?php $conn->close(); include 'footer.php'; ?>
