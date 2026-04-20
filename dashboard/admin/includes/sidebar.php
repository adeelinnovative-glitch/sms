<?php
if(basename($_SERVER['PHP_SELF']) == 'sidebar.php') {
    die('Direct access not permitted');
}
$currentPage = basename($_SERVER['PHP_SELF']);
// Automated cleanup for dashboard views
include_once(__DIR__ . "/../../../includes/cleanup.php");
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>ELEGANCE</h2>
        <small class="text-gold">Admin Panel</small>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">✦ Command Center</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'appointments.php' ? 'active' : '' ?>" href="appointments.php">✦ Appointments</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'calendar.php' ? 'active' : '' ?>" href="calendar.php">✦ Visual Calendar</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'inventory.php' ? 'active' : '' ?>" href="inventory.php">
                Inventory
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'staff.php' ? 'active' : '' ?>" href="staff.php">
                Staff Management
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'users.php' ? 'active' : '' ?>" href="users.php">
                User Accounts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'clients.php' ? 'active' : '' ?>" href="clients.php">
                Client Directory
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'reports.php' ? 'active' : '' ?>" href="reports.php">
                Reports & Analytics
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'feedbacks.php' ? 'active' : '' ?>" href="feedbacks.php">
                User Feedback
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link text-rose" href="../../logout.php">
                Logout
            </a>
        </li>
    </ul>
</div>
