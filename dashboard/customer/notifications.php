<?php
session_start();
if (!isset($_SESSION["name"])) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Get client_id
$email = $_SESSION["email"];
$cl_id = 0;
$q_id = mysqli_query($con, "SELECT client_id FROM clients WHERE email = '$email'");
if($r_id = mysqli_fetch_assoc($q_id)) $cl_id = $r_id['client_id'];

// Mark all as read when viewing this page
mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE user_id = $cl_id");

// Handle Empty Inbox (Mark Hidden)
if(isset($_POST['empty_inbox'])) {
    mysqli_query($con, "UPDATE notifications SET is_hidden = 1 WHERE user_id = $cl_id");
}
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>All Notifications</h3>
                <p class="text-muted mb-0">Review your past alerts and messages.</p>
            </div>
            <form action="" method="post">
                <button type="submit" name="empty_inbox" class="btn btn-outline-light btn-sm px-3" onclick="return confirm('This will hide all current notifications from your view. Continue?')">Empty Inbox</button>
            </form>
        </div>

        <div class="glass-card">
            <?php
            $res = mysqli_query($con, "SELECT * FROM notifications WHERE user_id = $cl_id AND is_hidden = 0 ORDER BY created_at DESC");
            if(mysqli_num_rows($res) > 0) {
                while($row = mysqli_fetch_assoc($res)) {
                    echo "
                    <div class='border-bottom border-light border-opacity-10 py-3'>
                        <div class='d-flex justify-content-between align-items-center mb-2'>
                            <span class='badge bg-dark text-gold border border-gold border-opacity-25'>System Alert</span>
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
