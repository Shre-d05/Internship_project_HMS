<?php
// $currentPage must be set by calling file before including this header
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
}
$username = $_SESSION['username'];
$initial  = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Hostel Management System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
    .sidebar-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(15,23,42,0.4);
        z-index: 99;
    }
    @media (max-width: 768px) {
        .sidebar-overlay.active { display: block; }
    }
    </style>
</head>
<body>
<div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-top">
                <div>
                    <h2>HMS</h2>
                    <p>Admin Portal</p>
                </div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar"><?= $initial ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($username) ?></div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                Dashboard
            </a>
            <a href="students.php" class="nav-link <?= $currentPage === 'students' ? 'active' : '' ?>">
                Students
            </a>
            <a href="rooms.php" class="nav-link <?= $currentPage === 'rooms' ? 'active' : '' ?>">
                Rooms
            </a>
            <div class="nav-section-label" style="margin-top:12px;">Management</div>
            <a href="complaints.php" class="nav-link <?= $currentPage === 'complaints' ? 'active' : '' ?>">
                Complaints
            </a>
            <a href="mess.php" class="nav-link <?= $currentPage === 'mess' ? 'active' : '' ?>">
                Mess & Menu
            </a>
            <a href="hostels.php" class="nav-link <?= $currentPage === 'hostels' ? 'active' : '' ?>">
                Hostels
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </aside>
    <!-- Mobile overlay for sidebar -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <!-- MAIN CONTENT -->
    <main class="main-content">
