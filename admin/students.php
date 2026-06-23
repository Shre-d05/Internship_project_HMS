<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireAdmin();

$conn   = getDBConnection();
$msg    = '';
$msgType= 'success';

// ── ADD STUDENT ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name   = sanitize($conn, $_POST['name']);
    $roll   = sanitize($conn, $_POST['roll_no']);
    $dept   = sanitize($conn, $_POST['department']);
    $year   = (int)$_POST['year'];
    $phone  = sanitize($conn, $_POST['phone']);
    $email  = sanitize($conn, $_POST['email']);
    $roomId = (int)$_POST['room_id'] ?: 'NULL';
    $pwd    = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check duplicate roll
    $chk = $conn->prepare("SELECT student_id FROM student WHERE roll_no=?");
    $chk->bind_param("s", $roll);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $msg = "Roll number already exists."; $msgType = 'error';
    } else {
        $conn->begin_transaction();
        try {
            // Create user account
            $stmtU = $conn->prepare("INSERT INTO users (username,password,role) VALUES (?,?,'student')");
            $stmtU->bind_param("ss", $roll, $pwd);
            $stmtU->execute();
            $userId = $conn->insert_id;

            // Create student record
            $roomVal = $roomId === 'NULL' ? null : $roomId;
            $stmtS = $conn->prepare("INSERT INTO student (user_id,name,roll_no,department,year,phone,email,room_id) VALUES (?,?,?,?,?,?,?,?)");
            $stmtS->bind_param("isssissi", $userId, $name, $roll, $dept, $year, $phone, $email, $roomVal);
            $stmtS->execute();

            // Mark room occupied if assigned
            if ($roomVal) {
                $conn->query("UPDATE room SET status='occupied' WHERE room_id=$roomVal");
            }
            $conn->commit();
            $msg = "Student '{$name}' added successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $msg = "Error: " . $e->getMessage(); $msgType = 'error';
        }
    }
}

// ── UPDATE STUDENT ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $sid    = (int)$_POST['student_id'];
    $name   = sanitize($conn, $_POST['name']);
    $dept   = sanitize($conn, $_POST['department']);
    $year   = (int)$_POST['year'];
    $phone  = sanitize($conn, $_POST['phone']);
    $email  = sanitize($conn, $_POST['email']);
    $roomId = (int)$_POST['room_id'] ?: null;

    // Get old room
    $old = $conn->query("SELECT room_id FROM student WHERE student_id=$sid")->fetch_assoc();
    $oldRoom = $old['room_id'];

    $stmtE = $conn->prepare("UPDATE student SET name=?,department=?,year=?,phone=?,email=?,room_id=? WHERE student_id=?");
    $stmtE->bind_param("ssissii", $name, $dept, $year, $phone, $email, $roomId, $sid);
    $stmtE->execute();

    // Update old room → available if different
    if ($oldRoom && $oldRoom != $roomId) {
        $conn->query("UPDATE room SET status='available' WHERE room_id=$oldRoom");
    }
    if ($roomId) {
        $conn->query("UPDATE room SET status='occupied' WHERE room_id=$roomId");
    }
    $msg = "Student updated successfully!";
}

// ── DELETE STUDENT ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $sid = (int)$_POST['student_id'];
    $stu = $conn->query("SELECT user_id, room_id FROM student WHERE student_id=$sid")->fetch_assoc();
    if ($stu) {
        if ($stu['room_id']) {
            $conn->query("UPDATE room SET status='available' WHERE room_id={$stu['room_id']}");
        }
        $conn->query("DELETE FROM complaint WHERE student_id=$sid");
        $conn->query("DELETE FROM student WHERE student_id=$sid");
        $conn->query("DELETE FROM users WHERE user_id={$stu['user_id']}");
        $msg = "Student deleted."; $msgType = 'error';
    }
}

// ── FETCH DATA ────────────────────────────────────────────────
$search = sanitize($conn, $_GET['search'] ?? '');
$whereClause = $search ? "WHERE s.name LIKE '%$search%' OR s.roll_no LIKE '%$search%' OR s.department LIKE '%$search%'" : '';

$students = $conn->query("
    SELECT s.*, r.room_number, r.room_type, h.name AS hostel_name
    FROM student s
    LEFT JOIN room r ON s.room_id = r.room_id
    LEFT JOIN hostel h ON r.hostel_id = h.hostel_id
    $whereClause
    ORDER BY s.student_id DESC
");

$rooms = $conn->query("SELECT r.room_id, r.room_number, r.room_type, r.capacity, r.status, h.name AS hostel_name FROM room r JOIN hostel h ON r.hostel_id=h.hostel_id WHERE r.status IN ('available','occupied') ORDER BY h.name, r.room_number");
$roomList = $rooms->fetch_all(MYSQLI_ASSOC);

$currentPage = 'students';
include 'header.php';
?>

<div class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">☰</button>
        <div>
            <h1>Students</h1>
            <p>Manage student registrations and room assignments.</p>
        </div>
    </div>
    <div class="topbar-right">
        <span class="topbar-badge">Admin</span>
    </div>
</div>

<div class="page-body page-body-wide">
<?php if ($msg): ?>
<div class="alert alert-<?= $msgType === 'error' ? 'error' : 'success' ?>" style="margin-bottom:16px;">
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<div class="page-actions">
    <form method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search by name, roll no, department…" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
        <?php if ($search): ?><a href="students.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
    </form>
    <button class="btn btn-primary" onclick="openModal('addModal')">Add Student</button>
</div>

<div class="card">
    <div class="card-header">
        <div><div class="card-title">All Students</div><div class="card-subtitle"><?= $students->num_rows ?> record(s) found</div></div>
    </div>
    <div class="card-body">
        <?php if ($students->num_rows > 0): ?>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Roll No.</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Contact</th>
                    <th>Room</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($s = $students->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= $i++ ?></td>
                    <td>
                        <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                        <div class="text-xs text-muted"><?= htmlspecialchars($s['email']) ?></div>
                    </td>
                    <td><code style="background:var(--gray-100);padding:2px 6px;border-radius:4px;font-size:0.8rem;"><?= htmlspecialchars($s['roll_no']) ?></code></td>
                    <td><?= htmlspecialchars($s['department']) ?></td>
                    <td><span class="badge badge-info">Year <?= $s['year'] ?></span></td>
                    <td>
                        <div class="text-sm"><?= htmlspecialchars($s['phone']) ?></div>
                    </td>
                    <td>
                        <?php if ($s['room_number']): ?>
                            <div class="fw-600"><?= htmlspecialchars($s['room_number']) ?></div>
                            <div class="text-xs text-muted"><?= htmlspecialchars($s['hostel_name']) ?> · <?= $s['room_type'] ?></div>
                        <?php else: ?>
                            <span class="badge badge-gray">Unassigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($s) ?>)'>Edit</button>
                            <form method="POST" onsubmit="return confirm('Delete this student permanently?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="student_id" value="<?= $s['student_id'] ?>">
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

            <h4>No students found</h4>
            <p><?= $search ? 'Try a different search term.' : 'Click "Add Student" to register the first student.' ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>
</div><!-- .page-body -->

<!-- ── ADD MODAL ── -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Add New Student</h4>
            <button class="modal-close" onclick="closeModal('addModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Rahul Sharma">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Roll Number *</label>
                        <input type="text" name="roll_no" class="form-control" required placeholder="e.g. 2024CS001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select name="department" class="form-control" required>
                            <option value="">Select Department</option>
                            <option>Computer Science</option>
                            <option>Electronics</option>
                            <option>Mechanical</option>
                            <option>Civil</option>
                            <option>Chemical</option>
                            <option>Mathematics</option>
                            <option>Physics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year *</label>
                        <select name="year" class="form-control" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="text" name="phone" class="form-control" required placeholder="10-digit number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required placeholder="student@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">— No Room —</option>
                            <?php foreach ($roomList as $r): ?>
                            <option value="<?= $r['room_id'] ?>" <?= $r['status']==='occupied' ? 'style="color:var(--gray-400)"' : '' ?>>
                                <?= htmlspecialchars($r['hostel_name']) ?> · Rm <?= htmlspecialchars($r['room_number']) ?> (<?= $r['room_type'] ?>, Cap:<?= $r['capacity'] ?>) [<?= ucfirst($r['status']) ?>]
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="text" name="password" class="form-control" required placeholder="Login password for student">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Student</button>
            </div>
        </form>
    </div>
</div>

<!-- ── EDIT MODAL ── -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Edit Student</h4>
            <button class="modal-close" onclick="closeModal('editModal')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_student_id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Roll No. (read-only)</label>
                        <input type="text" id="edit_roll_display" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select name="department" id="edit_department" class="form-control" required>
                            <option>Computer Science</option>
                            <option>Electronics</option>
                            <option>Mechanical</option>
                            <option>Civil</option>
                            <option>Chemical</option>
                            <option>Mathematics</option>
                            <option>Physics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year *</label>
                        <select name="year" id="edit_year" class="form-control" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">Assign Room</label>
                        <select name="room_id" id="edit_room_id" class="form-control">
                            <option value="">— No Room —</option>
                            <?php foreach ($roomList as $r): ?>
                            <option value="<?= $r['room_id'] ?>">
                                <?= htmlspecialchars($r['hostel_name']) ?> · Rm <?= htmlspecialchars($r['room_number']) ?> (<?= $r['room_type'] ?>) [<?= ucfirst($r['status']) ?>]
                            </option>
                            <?php endforeach; ?>
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
function openEditModal(s) {
    document.getElementById('edit_student_id').value = s.student_id;
    document.getElementById('edit_name').value        = s.name;
    document.getElementById('edit_roll_display').value= s.roll_no;
    document.getElementById('edit_phone').value       = s.phone;
    document.getElementById('edit_email').value       = s.email;
    // Set department select
    const dept = document.getElementById('edit_department');
    for (let o of dept.options) { if (o.value === s.department) { o.selected = true; break; } }
    // Set year
    const yr = document.getElementById('edit_year');
    for (let o of yr.options) { if (o.value == s.year) { o.selected = true; break; } }
    // Set room
    const rm = document.getElementById('edit_room_id');
    for (let o of rm.options) { if (o.value == s.room_id) { o.selected = true; break; } }
    openModal('editModal');
}
</script>

<?php
$conn->close();
include 'footer.php';
?>
