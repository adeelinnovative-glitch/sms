<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician' && $_SESSION["role"] !== 'nail technician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
// Get Staff Details linked to this user
$u_id = $_SESSION['id'];
$q_staff = mysqli_query($con, "SELECT * FROM staff WHERE user_id = $u_id");
$sData = mysqli_fetch_assoc($q_staff);
$sid = $sData['staff_id'] ?? 0;

// Get Commissions (Current Month)
$resComm = mysqli_query($con, "SELECT SUM(b.amount * s.commission_rate / 100) as total 
                               FROM billing b 
                               JOIN appointments a ON b.appointment_id = a.appointment_id 
                               JOIN staff s ON a.staff_id = s.staff_id 
                               WHERE s.staff_id = $sid AND MONTH(b.date) = MONTH(CURDATE()) AND YEAR(b.date) = YEAR(CURDATE())");
$earnings = mysqli_fetch_assoc($resComm)['total'] ?? 0;

include_once("../../header.php");
?>
<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>My Command Center</h3>
                <h6 class="text-gold mb-1"><?= strtoupper($sData['role']) ?></h6>
                <p class="text-muted mb-0">Hello, <?= htmlspecialchars($sData['name']) ?>. Here's your shift overview.</p>
                <div class="mt-2">
                    <span class="text-gold small"><i class="fas fa-calendar-alt me-1"></i> <?= htmlspecialchars($sData['schedule']) ?></span>
                    <span class="text-muted mx-2 small">|</span>
                    <span class="text-gold small"><i class="fas fa-clock me-1"></i> <?= htmlspecialchars($sData['time_slot']) ?></span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <?php
                // Check for unread cancellations PROMINENTLY for staff
                $checkCS = mysqli_query($con, "SELECT * FROM notifications WHERE user_id = $u_id AND is_read = 0 AND message LIKE '%cancelled%'");
                if($rowCS = mysqli_fetch_assoc($checkCS)) {
                    echo '<div class="alert alert-danger mb-0" style="background: rgba(183, 110, 121, 0.2); border-color: #B76E79; color: #fff;">
                            <strong>Schedule Alert:</strong> One of your scheduled sessions has been cancelled by an administrator. Please check your alerts below.
                          </div>';
                }
                // Mark all as read as they have seen this dashboard now
                mysqli_query($con, "UPDATE notifications SET is_read = 1 WHERE user_id = $u_id");
                ?>
            </div>
            <div class="col-md-7">
                <div class="glass-card">
                    <h5 class="text-gold">Your Upcoming Appointments</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover border-light mt-3 mb-0" style="background: transparent;">
                            <thead><tr><th class="text-gold small">Time</th><th class="text-gold small">Client</th><th class="text-gold small">Service</th></tr></thead>
                            <tbody>
                                <?php
                                $resA = mysqli_query($con, "SELECT a.time, a.service, c.name FROM appointments a JOIN clients c ON a.client_id = c.client_id WHERE a.staff_id = $sid AND a.status = 'pending' AND a.date >= CURDATE() ORDER BY a.date, a.time ASC LIMIT 3");
                                if(mysqli_num_rows($resA) > 0) {
                                    while($rowA = mysqli_fetch_assoc($resA)) {
                                        echo "<tr>
                                                <td class='small'>{$rowA['time']}</td>
                                                <td class='small'>{$rowA['name']}</td>
                                                <td class='small'>{$rowA['service']}</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-muted small py-3'>No upcoming sessions.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions moved below appointments -->
                <div class="glass-card mt-4">
                    <h6 class="text-gold mb-3">Quick Actions</h6>
                    <div class="d-grid gap-3">
                        <a href="appointments.php" class="btn-outline-gold btn-sm py-2" style="text-decoration: none;">
                            <i class="fas fa-calendar-alt me-2"></i> View Full Schedule
                        </a>
                        <a href="billing.php" class="btn-gold btn-sm py-2" style="text-decoration: none; color: #000; text-align: center; display: block;">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Generate New Invoice
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="glass-card mb-4">
                    <h5 class="text-gold">Your Personal Earnings</h5>
                    <h2 class="mt-3">$<?= number_format($earnings, 2) ?></h2>
                    <small class="text-muted">Current Month's commission (<?= $sData['commission_rate'] ?>% rate)</small>
                </div>

                <div class="glass-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-gold mb-0">Inbox & Alerts</h5>
                        <a href="notifications.php" class="text-gold small" style="text-decoration: none;">View All</a>
                    </div>
                    <?php
                    $resN = mysqli_query($con, "SELECT * FROM notifications WHERE user_id = $u_id ORDER BY created_at DESC LIMIT 3");
                    if(mysqli_num_rows($resN) > 0) {
                        while($rowN = mysqli_fetch_assoc($resN)) {
                            $isC = stripos($rowN['message'], 'cancelled') !== false;
                            echo "
                            <div class='border-bottom border-light border-opacity-10 py-2'>
                                <div class='d-flex justify-content-between'>
                                    <small class='".($isC ? 'text-rose' : 'text-gold')."'>".($isC ? 'Cancellation' : 'System Alert')."</small>
                                    <small class='text-muted' style='font-size: 0.7rem;'>".date('M d, H:i', strtotime($rowN['created_at']))."</small>
                                </div>
                                <p class='text-light mt-1 mb-0' style='font-size: 0.85rem;'>{$rowN['message']}</p>
                            </div>";
                        }
                    } else {
                        echo '<p class="text-muted small mt-2">No active alerts.</p>';
                    }
                    ?>
                </div>


            </div>
        </div>
    </div>
</div>
<?php include_once("../../footer.php"); ?>