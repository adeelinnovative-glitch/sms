<?php
session_start();
if (!isset($_SESSION["name"])) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../includes/notifications.php");
include_once("../../includes/utils.php");

if(!isset($_GET['id'])) {
    header("Location: history.php");
    exit;
}

$appid = intval($_GET['id']);
$email = $_SESSION["email"];

// Verify ownership and pending status
$q = mysqli_prepare($con, "SELECT a.*, s.name as stylist_name, s.schedule as stylist_schedule, s.time_slot as stylist_slot FROM appointments a JOIN clients c ON a.client_id = c.client_id JOIN staff s ON a.staff_id = s.staff_id WHERE a.appointment_id = ? AND c.email = ? AND a.status = 'pending'");
mysqli_stmt_bind_param($q, "is", $appid, $email);
mysqli_stmt_execute($q);
$res = mysqli_stmt_get_result($q);
$appData = mysqli_fetch_assoc($res);
mysqli_stmt_close($q);

if(!$appData) {
    echo "Access denied or appointment not eligible for rescheduling.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $datetime = $_POST['datetime'];
    $date = date('Y-m-d', strtotime($datetime));
    $time = date('H:i:s', strtotime($datetime));
    $staff_id = $appData['staff_id'];
    $staffSchedule = $appData['stylist_schedule'];
    $staffName = $appData['stylist_name'];

    // Check for past dates
    if(strtotime($datetime) < time()) {
        $error = "You cannot reschedule an appointment to a time that has already passed.";
    } else if (($av = checkStaffAvailability($datetime, $staffSchedule, $appData['stylist_slot'])) !== 'valid') {
        $error = "<strong>$staffName</strong> is off-duty at the requested time. Their shift today is: {$appData['stylist_slot']}";
    } else {
        // Check for conflicts (excluding this appointment)
        $check = mysqli_prepare($con, "SELECT appointment_id FROM appointments WHERE staff_id = ? AND date = ? AND time = ? AND status != 'cancelled' AND appointment_id != ?");
        mysqli_stmt_bind_param($check, "issi", $staff_id, $date, $time, $appid);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        $conflict = mysqli_stmt_num_rows($check);
        mysqli_stmt_close($check);

        if($conflict > 0) {
            $error = "The stylist is already booked at the new selected time. Please choose another slot.";
        } else {
            $upd = mysqli_prepare($con, "UPDATE appointments SET date = ?, time = ? WHERE appointment_id = ?");
            mysqli_stmt_bind_param($upd, "ssi", $date, $time, $appid);
            $success = mysqli_stmt_execute($upd);
            mysqli_stmt_close($upd);

            if($success) {
                sendNotification($con, $email, "Your appointment for {$appData['service']} has been rescheduled to $date at $time.", 'email', $appData['client_id']);
                
                // Notify Staff
                $sq = mysqli_query($con, "SELECT u.email FROM staff s JOIN users u ON s.user_id = u.id WHERE s.staff_id = $staff_id");
                if($sr = mysqli_fetch_assoc($sq)) {
                    sendNotification($con, $sr['email'], "Appointment Rescheduled: {$appData['service']} with {$_SESSION['name']} is now at $date $time.", 'email', $staff_id);
                }
                
                header("Location: history.php?rescheduled=1");
                exit;
            }
        }
    }
}
include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>Reschedule Appointment</h3>
                <p class="text-muted mb-0">Modify your booking for <strong><?= $appData['service'] ?></strong>.</p>
            </div>
        </div>

        <div class="glass-card">
            <?php if(isset($error)) echo '<div class="alert alert-danger">'.$error.'</div>'; ?>
            
            <form action="" method="post">
                <div class="mb-3">
                    <label class="form-label">Stylist</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($appData['stylist_name']) ?>" style="background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.1); color: #888;" readonly>
                    <small class="text-muted">Staff cannot be changed during rescheduling for consistency.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Date & Time</label>
                    <input type="datetime-local" name="datetime" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($appData['date'].' '.$appData['time'])) ?>" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn-gold px-4">Update Appointment</button>
                    <a href="history.php" class="btn btn-outline-light px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include_once("../../footer.php"); ?>
