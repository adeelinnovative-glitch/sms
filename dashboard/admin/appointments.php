<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
include_once("../../header.php");

// Handle status updates if Admin wants to override
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $appid = intval($_POST['appointment_id']);
    $nstatus = $_POST['new_status'];
    
    // Get details for notification - Using LEFT JOIN in case stylist was removed
    $notifQ = mysqli_query($con, "SELECT a.service, a.date, a.staff_id, c.name as c_name, c.email as c_email, s.name as s_name, s.user_id as s_user_id, u.email as s_email, c.client_id 
                                  FROM appointments a 
                                  JOIN clients c ON a.client_id = c.client_id 
                                  LEFT JOIN staff s ON a.staff_id = s.staff_id 
                                  LEFT JOIN users u ON s.user_id = u.id 
                                  WHERE a.appointment_id = $appid");
    
    if($nData = mysqli_fetch_assoc($notifQ)) {
        if (mysqli_query($con, "UPDATE appointments SET status = '$nstatus' WHERE appointment_id = $appid")) {
            if ($nstatus == 'cancelled') {
                $cancel_success = true;
                
                include_once("../../includes/notifications.php");
                // Notify Client
                $c_msg = "Your appointment for {$nData['service']} on {$nData['date']} has been CANCELLED by the salon administrator.";
                sendNotification($con, $nData['c_email'], $c_msg, 'email', $nData['client_id']);
                
                // Notify Staff/Stylist ONLY if they still exist
                if (!empty($nData['s_email']) && !empty($nData['s_user_id'])) {
                    $s_msg = "Appointment Cancelled by Admin: {$nData['service']} with {$nData['c_name']} on {$nData['date']}.";
                    sendNotification($con, $nData['s_email'], $s_msg, 'email', $nData['s_user_id']);
                }
            }
        }
    }
}

// Fetch statistics
$pending_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'"))['count'];
$cancelled_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM appointments WHERE status = 'cancelled'"))['count'];
$paid_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(DISTINCT a.appointment_id) as count FROM appointments a JOIN billing b ON a.appointment_id = b.appointment_id WHERE a.status = 'completed'"))['count'];
$unpaid_count = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM appointments a LEFT JOIN billing b ON a.appointment_id = b.appointment_id WHERE a.status = 'completed' AND b.bill_id IS NULL"))['count'];
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>Master Appointment Registry</h3>
                <p class="text-muted mb-0">View and manage all salon bookings across all stylists.</p>
            </div>
            <div>
                <a href="calendar.php" class="btn-gold"><i class="fas fa-calendar-alt me-2"></i>Switch to Calendar</a>
            </div>
        </div>

        <?php if(isset($cancel_success)) echo '<div class="alert alert-danger"><i class="fas fa-info-circle me-2"></i> Appointment Cancelled Successfully.</div>'; ?>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="glass-card text-center p-3 h-100 d-flex flex-column justify-content-center scale-hover" style="background: linear-gradient(145deg, rgba(255, 193, 7, 0.15) 0%, rgba(0, 0, 0, 0.4) 100%); border-top: 3px solid #ffc107; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.1);">
                    <h6 class="text-warning mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-clock fs-3 mb-2 d-block"></i>Pending</h6>
                    <h2 class="mb-0 text-light fw-bold"><?php echo $pending_count; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card text-center p-3 h-100 d-flex flex-column justify-content-center scale-hover" style="background: linear-gradient(145deg, rgba(13, 202, 240, 0.15) 0%, rgba(0, 0, 0, 0.4) 100%); border-top: 3px solid #0dcaf0; box-shadow: 0 4px 15px rgba(13, 202, 240, 0.1);">
                    <h6 class="text-info mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-exclamation-circle fs-3 mb-2 d-block"></i>Unpaid</h6>
                    <h2 class="mb-0 text-light fw-bold"><?php echo $unpaid_count; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card text-center p-3 h-100 d-flex flex-column justify-content-center scale-hover" style="background: linear-gradient(145deg, rgba(25, 135, 84, 0.15) 0%, rgba(0, 0, 0, 0.4) 100%); border-top: 3px solid #198754; box-shadow: 0 4px 15px rgba(25, 135, 84, 0.1);">
                    <h6 class="text-success mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-check-circle fs-3 mb-2 d-block"></i>Paid</h6>
                    <h2 class="mb-0 text-light fw-bold"><?php echo $paid_count; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card text-center p-3 h-100 d-flex flex-column justify-content-center scale-hover" style="background: linear-gradient(145deg, rgba(220, 53, 69, 0.15) 0%, rgba(0, 0, 0, 0.4) 100%); border-top: 3px solid #dc3545; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);">
                    <h6 class="text-danger mb-3 text-uppercase fw-bold" style="letter-spacing: 1px;"><i class="fas fa-times-circle fs-3 mb-2 d-block"></i>Cancelled</h6>
                    <h2 class="mb-0 text-light fw-bold"><?php echo $cancelled_count; ?></h2>
                </div>
            </div>
        </div>

        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h5 class="text-gold mb-0"><i class="fas fa-list-alt me-2"></i>All Appointments (Full History)</h5>
                <div class="search-box" style="min-width: 250px;">
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" id="appointmentSearch" class="form-control bg-dark text-light border-secondary shadow-none" placeholder="Search client or service..." onkeyup="filterAppointments()">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-light mb-0 align-middle" style="background: transparent;" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th class="text-gold border-bottom border-light pb-3">Date/Time</th>
                            <th class="text-gold border-bottom border-light pb-3">Client</th>
                            <th class="text-gold border-bottom border-light pb-3">Service</th>
                            <th class="text-gold border-bottom border-light pb-3">Stylist</th>
                            <th class="text-gold border-bottom border-light pb-3">Status</th>
                            <th class="text-gold border-bottom border-light pb-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = "SELECT a.*, c.name as client_name, s.name as staff_name, b.bill_id 
                              FROM appointments a 
                              JOIN clients c ON a.client_id = c.client_id 
                              LEFT JOIN staff s ON a.staff_id = s.staff_id 
                              LEFT JOIN billing b ON a.appointment_id = b.appointment_id
                              ORDER BY a.date DESC, a.time DESC";
                        $res = mysqli_query($con, $q);
                        while($row = mysqli_fetch_assoc($res)) {
                            $status_text = ucfirst($row['status']);
                            $badge = 'bg-secondary';
                            
                            if($row['status'] == 'completed') {
                                if(!empty($row['bill_id'])) {
                                    $status_text = "Paid";
                                    $badge = "bg-success";
                                } else {
                                    $status_text = "Unpaid";
                                    $badge = "bg-info text-dark"; // Light blue/cyan for attention
                                }
                            }
                            if($row['status'] == 'pending') $badge = 'bg-warning text-dark';
                            if($row['status'] == 'cancelled') $badge = 'bg-danger';

                            $staffDisp = $row['staff_name'] ? htmlspecialchars($row['staff_name']) : '<span class="badge bg-dark text-muted border border-secondary"><i class="fas fa-user-slash me-1"></i> Removed Stylist</span>';

                            echo "<tr>
                                <td>
                                    <div class='d-flex align-items-center'>
                                        <div class='bg-dark rounded p-2 me-3 text-center border border-secondary shadow-sm' style='min-width: 65px;'>
                                            <span class='d-block text-gold fw-bold fs-5'>" . date('d', strtotime($row['date'])) . "</span>
                                            <small class='text-muted text-uppercase' style='font-size: 0.65rem; letter-spacing: 1px;'>" . date('M', strtotime($row['date'])) . "</small>
                                        </div>
                                        <div>
                                            <div class='fw-bold text-light'>" . date('l', strtotime($row['date'])) . "</div>
                                            <small class='text-muted'><i class='fas fa-clock me-1'></i>{$row['time']}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class='fw-bold text-light'>" . htmlspecialchars($row['client_name']) . "</div>
                                </td>
                                <td>
                                    <div class='text-light'>" . htmlspecialchars($row['service']) . "</div>
                                </td>
                                <td>{$staffDisp}</td>
                                <td><span class='badge rounded-pill {$badge} px-3 py-2 fw-normal' style='letter-spacing: 0.5px;'>" . $status_text . "</span></td>
                                <td>";
                            if($row['status'] == 'pending') {
                                echo "
                                <form method='post' class='d-inline' onsubmit='return confirm(\"Are you sure you want to cancel this appointment?\");'>
                                    <input type='hidden' name='appointment_id' value='{$row['appointment_id']}'>
                                    <input type='hidden' name='new_status' value='cancelled'>
                                    <button type='submit' name='update_status' class='btn btn-sm btn-outline-danger rounded-pill px-3 py-1 scale-hover'>
                                        <i class='fas fa-times me-1'></i> Cancel
                                    </button>
                                </form>";
                            } else {
                                echo "<span class='text-muted'>--</span>";
                            }
                            echo "</td>
                            </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterAppointments() {
    var input, filter, table, tr, tdClient, tdService, i, txtValueClient, txtValueService;
    input = document.getElementById("appointmentSearch");
    filter = input.value.toUpperCase();
    table = document.getElementById("appointmentsTable");
    tr = table.getElementsByTagName("tr");

    for (i = 1; i < tr.length; i++) {
        tdClient = tr[i].getElementsByTagName("td")[1];
        tdService = tr[i].getElementsByTagName("td")[2];
        if (tdClient || tdService) {
            txtValueClient = tdClient.textContent || tdClient.innerText;
            txtValueService = tdService.textContent || tdService.innerText;
            if (txtValueClient.toUpperCase().indexOf(filter) > -1 || txtValueService.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

<?php include_once("../../footer.php"); ?>
