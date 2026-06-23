<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireStudent();

$conn      = getDBConnection();
$studentId = $_SESSION['student_id'];
$msg       = ''; $msgType = 'success';

// ── SUBMIT COMPLAINT ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    $category    = sanitize($conn, $_POST['category']);
    $description = sanitize($conn, $_POST['description']);
    if (empty($category) || empty($description)) {
        $msg = "Please fill in all fields."; $msgType = 'error';
    } else {
        $stmt = $conn->prepare("INSERT INTO complaint (student_id, category, description, status) VALUES (?,?,?,'pending')");
        $stmt->bind_param("iss", $studentId, $category, $description);
        $stmt->execute();
        $msg = "Complaint submitted successfully! We'll look into it.";
    }
}

// ── DELETE OWN COMPLAINT ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $cid = (int)$_POST['complaint_id'];
    // Ensure student owns this complaint
    $own = $conn->query("SELECT complaint_id FROM complaint WHERE complaint_id=$cid AND student_id=$studentId")->num_rows;
    if ($own) {
        $conn->query("DELETE FROM complaint WHERE complaint_id=$cid");
        $msg = "Complaint withdrawn."; $msgType = 'error';
    }
}

// ── FILTER ────────────────────────────────────────────────────
$filterStatus = sanitize($conn, $_GET['status'] ?? '');
$where = "WHERE student_id=$studentId";
if ($filterStatus) $where .= " AND status='$filterStatus'";

$complaints = $conn->query("SELECT * FROM complaint $where ORDER BY created_at DESC");

// Counts
$total     = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId AND status='pending'")->fetch_assoc()['c'];
$progress  = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId AND status='in_progress'")->fetch_assoc()['c'];
$resolved  = $conn->query("SELECT COUNT(*) AS c FROM complaint WHERE student_id=$studentId AND status='resolved'")->fetch_assoc()['c'];

$conn->close();
$currentPage = 'complaints';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>My Complaints</h1>
            <p>Submit and track your hostel complaints.</p>
        </div>
    </div>
    <div class="topbar-right"><span class="topbar-badge">Student</span></div>
</div>

<div class="page-body">

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:16px;">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Total</div></div></div>
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $pending ?></div><div class="stat-lbl">Pending</div></div></div>
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $progress ?></div><div class="stat-lbl">In Progress</div></div></div>
    <div class="stat-card"><div class="stat-data"><div class="stat-num"><?= $resolved ?></div><div class="stat-lbl">Resolved</div></div></div>
</div>

<div class="grid-2" style="align-items:start;">

    <!-- Submit New Complaint -->
    <div id="new" class="card">
        <div class="card-header"><div class="card-title">Submit New Complaint</div></div>
        <div class="card-body-pad">
            <form method="POST">
                <input type="hidden" name="action" value="submit">
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-control" required>
                        <option value="">Select Category</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Electricity">Electricity</option>
                        <option value="Water">Water / Plumbing</option>
                        <option value="Mess">Mess / Food</option>
                        <option value="Cleanliness">Cleanliness</option>
                        <option value="Security">Security</option>
                        <option value="Internet">Internet / Wi-Fi</option>
                        <option value="Noise">Noise</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="description" class="form-control" required rows="5"
                              placeholder="Describe your complaint in detail. Be specific about location, time, and nature of the issue."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Submit Complaint</button>
            </form>
        </div>
    </div>

    <!-- Complaint List -->
    <div>
        <!-- Filter -->
        <div class="flex gap-2" style="margin-bottom:16px;flex-wrap:wrap;">
            <?php $statuses = [''=> 'All', 'pending'=>'Pending', 'in_progress'=>'In Progress', 'resolved'=>'Resolved'];
            foreach ($statuses as $val => $label): ?>
            <a href="?status=<?= $val ?>" class="btn btn-<?= $filterStatus === $val ? 'primary' : 'outline' ?> btn-sm"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <?php if ($complaints->num_rows > 0): ?>
            <?php while ($c = $complaints->fetch_assoc()):
                $badge = match($c['status']) {
                    'pending'     => 'badge-warning',
                    'in_progress' => 'badge-info',
                    'resolved'    => 'badge-success',
                    default       => 'badge-gray'
                };
            ?>
            <div class="card" style="margin-bottom:12px;">
                <div class="card-body-pad">
                    <div class="flex justify-between items-center" style="margin-bottom:10px;">
                        <div class="flex items-center gap-2">
                            <span class="fw-600"><?= htmlspecialchars($c['category']) ?></span>
                            <span class="text-xs text-muted">#<?= $c['complaint_id'] ?></span>
                        </div>
                        <span class="badge <?= $badge ?>"><?= ucwords(str_replace('_',' ',$c['status'])) ?></span>
                    </div>
                    <p class="text-sm" style="color:var(--gray-700);margin-bottom:10px;line-height:1.6;"><?= htmlspecialchars($c['description']) ?></p>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-muted"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
                        <?php if ($c['status'] === 'pending'): ?>
                        <form method="POST" onsubmit="return confirm('Withdraw this complaint?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" style="color:var(--danger);border-color:var(--danger);">Withdraw</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
        <div class="card">
            <div class="empty-state" style="padding:48px;">
                <h4>No complaints found</h4>
                <p><?= $filterStatus ? 'No complaints with this status.' : 'Submit your first complaint using the form.' ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div><!-- .grid-2 -->
</div><!-- .page-body -->

<?php include 'footer.php'; ?>
