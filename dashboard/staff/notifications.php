<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'receptionist' && $_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Get staff_id
$u_id = $_SESSION["id"];
$sid = 0;
$q_staff = mysqli_query($con, "SELECT staff_id FROM staff WHERE user_id = $u_id");
if($sData = mysqli_fetch_assoc($q_staff)) $sid = $sData['staff_id'];

// Mark all as read when viewing this page
mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE user_id = $u_id");

// Handle Empty Inbox (Mark HIdden)
if(isset($_POST['empty_inbox'])) {
    mysqli_query($con, "UPDATE notifications SET is_hidden = 1 WHERE user_id = $u_id");
}
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>System Alerts & Inbox</h3>
                <p class="text-muted mb-0">Review notifications and updates for the salon staff.</p>
            </div>
            <form action="" method="post">
                <button type="submit" name="empty_inbox" class="btn btn-outline-light btn-sm px-3" onclick="return confirm('This will hide all current notifications from your view. Continue?')">Empty Inbox</button>
            </form>
        </div>

        <div class="glass-card">
            <?php
            $res = mysqli_query($con, "SELECT * FROM notifications WHERE user_id = $u_id AND is_hidden = 0 ORDER BY created_at DESC");
            if(mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    $isCancellation = stripos($row['message'], 'cancelled') !== false;
                    $highlightClass = $isCancellation ? 'border-rose' : 'border-light';
                    $badgeClass = $isCancellation ? 'bg-danger' : 'bg-dark';
                    
                    echo "
                    <div class='border-bottom {$highlightClass} border-opacity-10 py-3'>
                        <div class='d-flex justify-content-between align-items-center mb-2'>
                            <span class='badge {$badgeClass} text-white border border-light border-opacity-25'>
                                " . ($isCancellation ? 'Cancellation' : 'System Alert') . "
                            </span>
                            <small class='text-muted'>".date('M d, Y @ H:i', strtotime($row['created_at']))."</small>
                        </div>
                        <p class='text-light mb-0'>{$row['message']}</p>
                    </div>";
                }
            } else {
                echo "<div class='text-center py-5'><p class='text-muted'>No notifications found.</p></div>";
            }
            ?>
        </div>
    </div>
</div>

<?php include_once("../../footer.php"); ?>
