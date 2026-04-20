<?php
session_start();
if (!isset($_SESSION["name"])) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../includes/notifications.php");

// Get client_id
$email = $_SESSION["email"];
$client_id = 0;
$stmt = mysqli_prepare($con, "SELECT client_id FROM clients WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if($row = mysqli_fetch_assoc($res)) {
    $client_id = $row['client_id'];
}
mysqli_stmt_close($stmt);

// Handle Cancellation
if(isset($_POST['cancel_appointment'])) {
    $appid = intval($_POST['appointment_id']);
    
    // Get details for notification before status update - Using LEFT JOIN in case stylist was removed
    $notifQ = mysqli_query($con, "SELECT a.service, a.date, a.time, a.staff_id, s.name as s_name, u.email as s_email FROM appointments a LEFT JOIN staff s ON a.staff_id = s.staff_id LEFT JOIN users u ON s.user_id = u.id WHERE a.appointment_id = $appid");
    if($nData = mysqli_fetch_assoc($notifQ)) {
        // Ensure the appointment belongs to the logged-in client
        $upd = mysqli_query($con, "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $appid AND client_id = $client_id");
        
        if($upd) {
            $cancel_success = true;
            // Notify Customer
            sendNotification($con, $_SESSION['email'], "You have cancelled your appointment for {$nData['service']} on {$nData['date']}.", 'email', $client_id);
            // Notify Staff ONLY if they still exist
            if (!empty($nData['s_email'])) {
                sendNotification($con, $nData['s_email'], "Appointment Cancelled by Client: {$nData['service']} with {$_SESSION['name']} on {$nData['date']}.", 'email', $nData['staff_id']);
            }
        }
    }
}

$rescheduled = isset($_GET['rescheduled']);

// Handle Feedback Submission
if(isset($_POST['submit_customer_feedback'])) {
    $f_name = mysqli_real_escape_string($con, $_SESSION['name']);
    $f_msg = mysqli_real_escape_string($con, trim($_POST['feedback_message']));
    if($f_msg !== '') {
        $ins_f = mysqli_query($con, "INSERT INTO feedbacks (name, message) VALUES ('$f_name', '$f_msg')");
        if($ins_f) $f_success = true;
    }
}

include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>History & Feedback</h3>
                <p class="text-muted mb-0">View your past visits and share your thoughts.</p>
            </div>
        </div>

        <?php if(isset($cancel_success)) echo '<div class="alert alert-danger">Appointment Cancelled Successfully.</div>'; ?>
        <?php if($rescheduled) echo '<div class="alert alert-info" style="background: rgba(255, 193, 7, 0.2); border-color: #ffc107; color: #ffc107;">Appointment Rescheduled Successfully.</div>'; ?>
        
        <div class="glass-card">
            <h5 class="text-gold mb-3">Past Appointments</h5>
            <table class="table table-dark table-hover border-light" style="background: transparent;">
                <thead>
                    <tr>
                        <th class="text-gold">Date & Time</th>
                        <th class="text-gold">Service</th>
                        <th class="text-gold">Stylist</th>
                        <th class="text-gold">Status</th>
                        <th class="text-gold text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if($client_id > 0) {
                        $q = "SELECT a.appointment_id, a.date, a.time, a.service, a.status, s.name as staff_name FROM appointments a LEFT JOIN staff s ON a.staff_id = s.staff_id WHERE a.client_id = ? ORDER BY a.date DESC";
                        $stmt = mysqli_prepare($con, $q);
                        mysqli_stmt_bind_param($stmt, "i", $client_id);
                        mysqli_stmt_execute($stmt);
                        $res = mysqli_stmt_get_result($stmt);
                        while($row = mysqli_fetch_assoc($res)) {
                            $badge = $row['status'] == 'completed' ? 'bg-success' : ($row['status'] == 'pending' ? 'bg-warning' : 'bg-danger');
                            echo "<tr>
                                <td>{$row['date']} {$row['time']}</td>
                                <td>{$row['service']}</td>
                                <td>{$row['staff_name']}</td>
                                <td><span class='badge {$badge}'>{$row['status']}</span></td>
                                <td class='text-end'>";
                            if ($row['status'] == 'pending') {
                                echo "
                                <form method='post' class='d-inline' onsubmit='return confirm(\"Cancel this appointment?\")'>
                                    <input type='hidden' name='appointment_id' value='{$row['appointment_id']}'>
                                    <button type='submit' name='cancel_appointment' class='text-rose bg-transparent border-0 small p-0'>Cancel</button>
                                </form>
                                <a href='../../download_ics.php?id={$row['appointment_id']}' class='text-gold small ms-2'>Sync Calendar</a>
                                <a href='reschedule.php?id={$row['appointment_id']}' class='text-info small ms-2'>Reschedule</a>";
                            }
                            echo "</td>
                            </tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        <div class="glass-card mt-4">
            <h5 class="text-gold mb-3">Rate Your Experience</h5>
            <p class="text-muted small">We value your feedback. Let us know how we can improve our services.</p>
            <?php if(isset($f_success)) echo '<div class="alert alert-success">Thank you for your feedback!</div>'; ?>
            <form action="" method="post">
                <div class="mb-3">
                    <textarea name="feedback_message" class="form-control" rows="4" placeholder="Share your experience with us..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" name="submit_customer_feedback" class="btn-gold px-5">Submit Feedback</button>
                </div>
            </form>
        </div>
        <div class="glass-card mt-4">
            <h5 class="text-gold mb-4">Your Previous Feedback</h5>
            <?php
            $u_name = mysqli_real_escape_string($con, $_SESSION['name']);
            $resPrev = mysqli_query($con, "SELECT * FROM feedbacks WHERE name = '$u_name' ORDER BY date DESC");
            if(mysqli_num_rows($resPrev) > 0) {
                while($rowP = mysqli_fetch_assoc($resPrev)) {
                    echo "
                    <div class='border-bottom border-light border-opacity-10 py-2 mb-2'>
                        <div class='d-flex justify-content-between mb-1'>
                            <small class='text-gold'>Submitted On</small>
                            <small class='text-muted'>".date('M d, Y', strtotime($rowP['date']))."</small>
                        </div>
                        <p class='text-light mb-0 italic'>\"{$rowP['message']}\"</p>
                    </div>";
                }
            } else {
                echo "<p class='text-muted'>You haven't submitted any feedback yet.</p>";
            }
            ?>
        </div>
    </div>
</div>
<?php include_once("../../footer.php"); ?>
