<?php
session_start();
// Since customer role is the default or 'customer', we check if not admin and not staff (or explicitly 'customer')
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'customer' && $_SESSION["role"] !== '')) {
    // Note: older logic might not have set role explicitly for some, but signup sets 'customer'
    // Let's assume customer is the fallback role if logged in
    if(!isset($_SESSION["name"])){
        header("Location: ../../login.php");
        exit;
    }
}
include_once("../../db.php");
include_once("../../header.php");
?>
<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>Welcome, <?= htmlspecialchars($_SESSION["name"]) ?>!</h3>
                <p class="text-muted mb-0">Ready for your next styling session?</p>
            </div>
            <div>
                <a href="book.php" class="btn-gold">Book New Appointment</a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div class="glass-card mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="text-gold mb-0">Upcoming Appointments</h5>
                        <a href="history.php" class="text-gold small" style="text-decoration: none;">View All →</a>
                    </div>
                    <?php
                    $email = $_SESSION["email"];
                    $cl_id = 0;
                    $q_id = mysqli_query($con, "SELECT client_id FROM clients WHERE email = '$email'");
                    if($r_id = mysqli_fetch_assoc($q_id)) $cl_id = $r_id['client_id'];

                    // Check for unread cancellations PROMINENTLY
                    $checkC = mysqli_query($con, "SELECT * FROM notifications WHERE user_id = $cl_id AND is_read = 0 AND message LIKE '%cancelled%'");
                    if($rowC = mysqli_fetch_assoc($checkC)) {
                        echo '<div class="alert alert-danger mb-4" style="background: rgba(183, 110, 121, 0.2); border-color: #B76E79; color: #fff;">
                                <strong>Important Notice:</strong> One or more of your appointments have been cancelled. Please check your Inbox below for details.
                              </div>';
                    }

                    // Mark as read when dashboard is opened as requested
                    mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE user_id = $cl_id");

                    $resA = mysqli_query($con, "SELECT date, time, service FROM appointments WHERE client_id = $cl_id AND date >= CURDATE() AND status = 'pending' ORDER BY date ASC LIMIT 3");
                    if(mysqli_num_rows($resA) > 0) {
                        echo '<ul class="list-group mt-3 bg-transparent">';
                        while($rowA = mysqli_fetch_assoc($resA)) {
                            echo "<li class='list-group-item bg-transparent text-light border-light d-flex justify-content-between'>
                                    <span>{$rowA['service']}</span>
                                    <small class='text-gold'>{$rowA['date']} @ {$rowA['time']}</small>
                                  </li>";
                        }
                        echo '</ul>';
                    } else {
                        echo '<p class="text-muted mt-3">You have no upcoming appointments.</p>';
                    }
                    ?>
                </div>

                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-gold mb-0">Inbox & Notifications</h5>
                        <a href="notifications.php" class="text-gold small" style="text-decoration: none;">View All →</a>
                    </div>
                    <?php
                    $resN = mysqli_query($con, "SELECT * FROM notifications WHERE user_id = $cl_id ORDER BY created_at DESC LIMIT 3");
                    if(mysqli_num_rows($resN) > 0) {
                        while($rowN = mysqli_fetch_assoc($resN)) {
                            echo "
                            <div class='border-bottom border-light border-opacity-10 py-3'>
                                <div class='d-flex justify-content-between'>
                                    <small class='text-gold'>System Alert</small>
                                    <small class='text-muted'>".date('M d, H:i', strtotime($rowN['created_at']))."</small>
                                </div>
                                <p class='text-light mt-1 mb-0' style='font-size: 0.9rem;'>{$rowN['message']}</p>
                            </div>";
                        }
                    } else {
                        echo '<p class="text-muted mt-3">Your inbox is empty.</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card mb-4">
                    <h5 class="text-gold">Your Feedback History</h5>
                    <?php
                    $u_name = mysqli_real_escape_string($con, $_SESSION['name']);
                    $resF = mysqli_query($con, "SELECT * FROM feedbacks WHERE name = '$u_name' ORDER BY date DESC LIMIT 2");
                    if(mysqli_num_rows($resF) > 0) {
                        while($rowF = mysqli_fetch_assoc($resF)) {
                            echo "<div class='mt-3 mb-2'>
                                    <p class='text-light italic mb-1' style='font-size: 0.85rem;'>\"{$rowF['message']}\"</p>
                                    <small class='text-muted'>".date('M d, Y', strtotime($rowF['date']))."</small>
                                  </div><hr class='border-secondary m-0'>";
                        }
                        echo '<div class="text-end mt-2"><a href="history.php" class="text-gold small">View All</a></div>';
                    } else {
                        echo '<p class="text-muted mt-3">No feedback submitted yet.</p>';
                    }
                    ?>
                </div>

                <div class="glass-card">
                    <h5 class="text-gold">Quick Actions</h5>
                    <ul class="list-group mt-3" style="background: transparent;">
                        <li class="list-group-item bg-transparent text-light border-light p-2"><a href="book.php" class="text-decoration-none text-light">✦ Book New Service</a></li>
                        <li class="list-group-item bg-transparent text-light border-light p-2"><a href="history.php" class="text-decoration-none text-light">✦ View Past Tickets</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once("../../footer.php"); ?>