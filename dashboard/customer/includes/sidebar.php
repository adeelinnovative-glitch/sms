<?php
if(basename($_SERVER['PHP_SELF']) == 'sidebar.php') {
    die('Direct access not permitted');
}
// Automated cleanup for dashboard views
include_once(__DIR__ . "/../../../includes/cleanup.php");
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header text-center">
        <?php
        $em_side = $_SESSION['email'];
        $pic_q = mysqli_query($con, "SELECT profile_pic FROM clients WHERE email = '$em_side'");
        $pic_d = mysqli_fetch_assoc($pic_q);
        $s_pic = !empty($pic_d['profile_pic']) ? "../../assets/profile_pics/" . $pic_d['profile_pic'] : "../../assets/img/default-user.png";
        ?>
        <div class="mx-auto mb-3" style="width: 70px; height: 70px; border-radius: 50%; border: 2px solid var(--gold); overflow: hidden; background: #222;">
            <img src="<?= $s_pic ?>?v=<?= time() ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <h2>ELEGANCE</h2>
        <small class="text-gold">Client Portal</small>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="index.php">
                My Dashboard
                <?php
                if(isset($_SESSION['email'])) {
                    include_once(__DIR__ . "/../../../db.php");
                    $em = $_SESSION['email'];
                    $un_q = mysqli_query($con, "SELECT COUNT(*) as unread FROM notifications n JOIN clients c ON n.user_id = c.client_id WHERE c.email = '$em' AND n.is_read = 0");
                    $un_d = mysqli_fetch_assoc($un_q);
                    if($un_d['unread'] > 0) {
                        echo '<span style="display: inline-block; width: 8px; height: 8px; background: #ff4d4d; border-radius: 50%; border: 1px solid white;"></span>';
                    }
                }
                ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'book.php' ? 'active' : '' ?>" href="book.php">
                Book Appointment
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'history.php' ? 'active' : '' ?>" href="history.php">
                History & Feedback
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
                Inbox & Notifications
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link <?= $currentPage == 'profile.php' ? 'active' : '' ?>" href="profile.php">
                ✦ My Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-info" href="../../index.php">
                <i class="fas fa-home me-2"></i> Back to Home
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-rose" href="../../logout.php">
                Logout
            </a>
        </li>
    </ul>
</div>
