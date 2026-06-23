<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireStudent();

$conn      = getDBConnection();
$studentId = $_SESSION['student_id'];
$msg       = ''; $msgType = 'success';

// ── UPDATE PROFILE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $phone = sanitize($conn, $_POST['phone']);
    $email = sanitize($conn, $_POST['email']);
    $stmt  = $conn->prepare("UPDATE student SET phone=?, email=? WHERE student_id=?");
    $stmt->bind_param("ssi", $phone, $email, $studentId);
    $stmt->execute();
    $msg = "Profile updated successfully!";
}

// ── CHANGE PASSWORD ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $oldPwd  = $_POST['old_password'];
    $newPwd  = $_POST['new_password'];
    $confPwd = $_POST['confirm_password'];

    // Get current hash
    $userId = $_SESSION['user_id'];
    $userRow = $conn->query("SELECT password FROM users WHERE user_id=$userId")->fetch_assoc();
    if (!password_verify($oldPwd, $userRow['password'])) {
        $msg = "Current password is incorrect."; $msgType = 'error';
    } elseif ($newPwd !== $confPwd) {
        $msg = "New passwords do not match."; $msgType = 'error';
    } elseif (strlen($newPwd) < 6) {
        $msg = "Password must be at least 6 characters."; $msgType = 'error';
    } else {
        $hash = password_hash($newPwd, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $hash, $userId);
        $stmt->execute();
        $msg = "Password changed successfully!";
    }
}

// ── Fetch ─────────────────────────────────────────────────────
$student = $conn->query("
    SELECT s.*, r.room_number, r.room_type, h.name AS hostel_name
    FROM student s
    LEFT JOIN room r ON s.room_id=r.room_id
    LEFT JOIN hostel h ON r.hostel_id=h.hostel_id
    WHERE s.student_id=$studentId
")->fetch_assoc();

$conn->close();
$currentPage = 'profile';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>My Profile</h1>
            <p>View and update your personal information.</p>
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

<div class="grid-2" style="align-items:start;">

    <!-- Profile Card -->
    <div class="card">
        <div style="background:linear-gradient(135deg,var(--primary),var(--accent));padding:32px 24px;text-align:center;">
            <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:white;margin:0 auto 14px;">
                <?= strtoupper(substr($student['name'], 0, 1)) ?>
            </div>
            <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:700;color:white;"><?= htmlspecialchars($student['name']) ?></div>
            <div style="font-size:0.8rem;color:rgba(255,255,255,0.75);margin-top:4px;"><?= htmlspecialchars($student['roll_no']) ?></div>
            <div style="margin-top:10px;"><span style="background:rgba(255,255,255,0.15);color:white;padding:3px 12px;border-radius:20px;font-size:0.75rem;">Student</span></div>
        </div>
        <div class="card-body-pad">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <?php $fields = [
                    ['label'=>'Department', 'value'=>$student['department']],
                    ['label'=>'Year',       'value'=>'Year ' . $student['year']],
                    ['label'=>'Phone',      'value'=>$student['phone']],
                    ['label'=>'Email',      'value'=>$student['email']],
                    ['label'=>'Room',       'value'=>$student['room_number'] ? 'Room ' . $student['room_number'] . ' · ' . $student['hostel_name'] : 'Not Assigned'],
                    ['label'=>'Room Type',  'value'=>$student['room_type'] ?? '—'],
                ];
                foreach ($fields as $f): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:12px;border-bottom:1px solid var(--gray-100);">
                    <span class="text-xs text-muted fw-600" style="text-transform:uppercase;letter-spacing:0.4px;"><?= $f['label'] ?></span>
                    <span class="fw-600 text-sm"><?= htmlspecialchars($f['value']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px;">

        <!-- Edit Profile -->
        <div class="card">
            <div class="card-header"><div class="card-title">Edit Contact Info</div></div>
            <div class="card-body-pad">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header"><div class="card-title">Change Password</div></div>
            <div class="card-body-pad">
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="old_password" class="form-control" required placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="Min 6 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter new password">
                    </div>
                    <button type="submit" class="btn btn-warning">Update Password</button>
                </form>
            </div>
        </div>

    </div>
</div><!-- .grid-2 -->
</div><!-- .page-body -->

<?php include 'footer.php'; ?>
