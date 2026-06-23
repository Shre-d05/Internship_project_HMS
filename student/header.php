<?php
require_once __DIR__ . '/../includes/auth.php';
requireStudent();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$studentName = $_SESSION['student_name'] ?? $_SESSION['username'];
$initial     = strtoupper(substr($studentName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student — Hostel Management System</title>
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
                    <p>Student Portal</p>
                </div>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar"><?= $initial ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($studentName) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">My Portal</div>
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                Dashboard
            </a>
            <a href="profile.php" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                My Profile
            </a>
            <a href="room.php" class="nav-link <?= $currentPage === 'room' ? 'active' : '' ?>">
                My Room
            </a>
            <div class="nav-section-label" style="margin-top:12px;">Services</div>
            <a href="complaints.php" class="nav-link <?= $currentPage === 'complaints' ? 'active' : '' ?>">
                My Complaints
            </a>
            <a href="mess_menu.php" class="nav-link <?= $currentPage === 'mess_menu' ? 'active' : '' ?>">
                Mess Menu
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
