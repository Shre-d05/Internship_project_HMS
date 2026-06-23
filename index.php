<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body class="login-body">

<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                // Fetch student_id for session
                $conn2 = getDBConnection();
                $stmt2 = $conn2->prepare("SELECT student_id, name FROM student WHERE user_id = ?");
                $stmt2->bind_param("i", $user['user_id']);
                $stmt2->execute();
                $r2 = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                $conn2->close();
                if ($r2) {
                    $_SESSION['student_id']   = $r2['student_id'];
                    $_SESSION['student_name'] = $r2['name'];
                }
                header('Location: student/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}
?>

<div class="login-container">
    <div class="login-left">
        <div class="login-brand">
            <div>
                <h1>HMS</h1>
                <p>Hostel Management System</p>
            </div>
        </div>
        <div class="login-tagline">
            <h2>Seamless Hostel<br>Administration</h2>
            <p>Manage rooms, complaints, mess menus, and student records — all from one place.</p>
        </div>
        <div class="login-features">
            <div class="feat-item"><span class="feat-dot"></span>Room Allocation & Tracking</div>
            <div class="feat-item"><span class="feat-dot"></span>Complaint Management</div>
            <div class="feat-item"><span class="feat-dot"></span>Mess Menu Scheduling</div>
            <div class="feat-item"><span class="feat-dot"></span>Student Records & Dashboard</div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <div class="login-card-header">
                <h3>Welcome Back</h3>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username / Roll Number</label>
                    <div class="input-wrap">
                        <input type="text" id="username" name="username" placeholder="Enter your username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">Sign In →</button>
            </form>

        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
