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
        include_once(__DIR__ . "/../../../db.php");
        $u_id_side = $_SESSION['id'];
        $pic_q = mysqli_query($con, "SELECT profile_pic, is_profile_updated FROM staff WHERE user_id = $u_id_side");
        $pic_d = mysqli_fetch_assoc($pic_q);
        $s_pic = !empty($pic_d['profile_pic']) ? "../../assets/profile_pics/" . $pic_d['profile_pic'] : "../../assets/img/default-user.png";
        ?>
        <div class="mx-auto mb-3" style="width: 70px; height: 70px; border-radius: 50%; border: 2px solid var(--accent-gold); overflow: hidden; background: #222;">
            <img src="<?= $s_pic ?>?v=<?= time() ?>" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <h2>ELEGANCE</h2>
        <small class="text-gold"><?php echo ucfirst($_SESSION['role']); ?> Portal</small>
    </div>
    <ul class="nav-menu">
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">
                My Shift
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'appointments.php' ? 'active' : '' ?>" href="appointments.php">
                My Schedule
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'calendar.php' ? 'active' : '' ?>" href="calendar.php">
                Visual Calendar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'billing.php' ? 'active' : '' ?>" href="billing.php">
                Billing & Checkout
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentPage == 'notifications.php' ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="notifications.php">
                Alerts & Inbox
                <?php
                include_once(__DIR__ . "/../../../db.php");
                $u_id = $_SESSION['id'];
                $un_q = mysqli_query($con, "SELECT COUNT(*) as unread FROM notifications WHERE user_id = $u_id AND is_read = 0");
                $un_d = mysqli_fetch_assoc($un_q);
                if($un_d['unread'] > 0) {
                    echo '<span style="display: inline-block; width: 8px; height: 8px; background: #ff4d4d; border-radius: 50%; border: 1px solid white;"></span>';
                }
                ?>
            </a>
        </li>
        <li class="nav-item mt-4">
            <a class="nav-link <?= $currentPage == 'profile.php' ? 'active' : '' ?> d-flex justify-content-between align-items-center" href="profile.php">
                ✦ My Profile
                <?php
                if(($pic_d['is_profile_updated'] ?? 0) == 1) {
                    echo '<span style="display: inline-block; width: 8px; height: 8px; background: #ff4d4d; border-radius: 50%; border: 1px solid white;"></span>';
                }
                ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-rose" href="../../logout.php">
                Logout
            </a>
        </li>
    </ul>
</div>
