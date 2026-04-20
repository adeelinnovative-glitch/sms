<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician' && $_SESSION["role"] !== 'nail technician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $appid = intval($_POST['appointment_id']);
    $nstatus = $_POST['new_status'];
    
    // Get details for notification
    $notifQ = mysqli_query($con, "SELECT a.service, a.date, a.staff_id, c.name as c_name, c.email as c_email, s.name as s_name, u.email as s_email, c.client_id, u.id as s_user_id 
                                  FROM appointments a 
                                  JOIN clients c ON a.client_id = c.client_id 
                                  LEFT JOIN staff s ON a.staff_id = s.staff_id 
                                  LEFT JOIN users u ON s.user_id = u.id 
                                  WHERE a.appointment_id = $appid");
    
    if($nData = mysqli_fetch_assoc($notifQ)) {
        $u = mysqli_prepare($con, "UPDATE appointments SET status = ? WHERE appointment_id = ?");
        mysqli_stmt_bind_param($u, "si", $nstatus, $appid);
        if(mysqli_stmt_execute($u)) {
            $msg = ($nstatus == 'completed') ? "Your appointment for {$nData['service']} is marked as COMPLETED." : "Your appointment for {$nData['service']} has been CANCELLED by the salon.";
            $smsg = ($nstatus == 'completed') ? "Appointment Completed: {$nData['service']} with {$nData['c_name']}." : "Appointment Cancelled: {$nData['service']} with {$nData['c_name']}.";
            
            include_once("../../includes/notifications.php");
            // Notify Client
            sendNotification($con, $nData['c_email'], $msg, 'email', $nData['client_id']);
            // Notify Staff
            if (!empty($nData['s_email'])) {
                sendNotification($con, $nData['s_email'], $smsg, 'email', $nData['s_user_id']);
            }
        }
        mysqli_stmt_close($u);
    }
}

include_once("../../header.php");
?>

<div class="dashboard-wrapper">
    <?php include_once("includes/sidebar.php"); ?>
    
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3>My Schedule & Appointments</h3>
                <p class="text-muted mb-0">Manage your bookings and track payment status.</p>
            </div>
            <div>
                <a href="calendar.php" class="btn-gold">Visual Calendar</a>
            </div>
        </div>

        <div class="glass-card">
            <h5 class="text-gold mb-4">Appointments Registry</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover border-light mb-0" style="background: transparent;">
                    <thead>
                        <tr>
                            <th class="text-gold">Date & Time</th>
                            <th class="text-gold">Client</th>
                            <th class="text-gold">Service</th>
                            <th class="text-gold">Status</th>
                            <th class="text-gold text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $u_id = $_SESSION['id'];
                        $q_staff = mysqli_query($con, "SELECT staff_id FROM staff WHERE user_id = $u_id");
                        $sid = mysqli_fetch_assoc($q_staff)['staff_id'] ?? 0;

                        $q = "SELECT a.appointment_id, a.date, a.time, a.service, a.status, c.name as client_name, b.bill_id 
                              FROM appointments a 
                              JOIN clients c ON a.client_id = c.client_id
                              LEFT JOIN billing b ON a.appointment_id = b.appointment_id
                              WHERE a.staff_id = $sid
                              ORDER BY a.status='pending' DESC, a.date DESC, a.time DESC";
                        
                        $res = mysqli_query($con, $q);
                        while($row = mysqli_fetch_assoc($res)) {
                            $status_text = ucfirst($row['status']);
                            $badge = 'bg-secondary';
                            
                            if($row['status'] == 'completed') {
                                if($row['bill_id']) {
                                    $status_text = "Payment Received";
                                    $badge = "bg-success";
                                } else {
                                    $status_text = "Finished (Unpaid)";
                                    $badge = "bg-info text-dark";
                                }
                            } elseif($row['status'] == 'pending') {
                                $badge = 'bg-warning text-dark';
                            } elseif($row['status'] == 'cancelled') {
                                $badge = 'bg-danger';
                            }

                            echo "<tr>
                                <td>" . date('M d, Y', strtotime($row['date'])) . " <br><small class='text-muted'>{$row['time']}</small></td>
                                <td>" . htmlspecialchars($row['client_name']) . "</td>
                                <td>" . htmlspecialchars($row['service']) . "</td>
                                <td><span class='badge {$badge}'>{$status_text}</span></td>
                                <td class='text-end'>";
                            
                            if ($row['status'] == 'pending') {
                                echo "<form method='post' class='d-inline me-1'>
                                    <input type='hidden' name='appointment_id' value='{$row['appointment_id']}'>
                                    <input type='hidden' name='new_status' value='completed'>
                                    <button type='submit' name='update_status' class='btn btn-sm btn-success px-2 py-0' style='font-size:0.75rem;'>Complete</button>
                                </form>";
                                echo "<form method='post' class='d-inline'>
                                    <input type='hidden' name='appointment_id' value='{$row['appointment_id']}'>
                                    <input type='hidden' name='new_status' value='cancelled'>
                                    <button type='submit' name='update_status' class='btn btn-sm btn-outline-danger px-2 py-0' style='font-size:0.75rem;'>Cancel</button>
                                </form>";
                            } elseif ($row['status'] == 'completed' && !$row['bill_id']) {
                                echo "<a href='billing.php?app_id={$row['appointment_id']}' class='btn btn-sm btn-gold px-2 py-0' style='font-size:0.75rem; color:#000;'>Generate Invoice</a>";
                            }
                            
                            echo "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
            </table>
        </div>
    </div>
</div>
<?php include_once("../../footer.php"); ?>
