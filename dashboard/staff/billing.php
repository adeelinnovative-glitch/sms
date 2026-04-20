<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician' && $_SESSION["role"] !== 'nail technician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");
$u_id = $_SESSION['id'];
$q_staff = mysqli_query($con, "SELECT staff_id FROM staff WHERE user_id = $u_id");
$sData = mysqli_fetch_assoc($q_staff);
$sid_current = $sData['staff_id'] ?? 0;

$prices = [
    'Hair Styling' => 50,
    'Manicure' => 30,
    'Pedicure' => 40,
    'Facial' => 60,
    'Haircut' => 35,
    'Hair Coloring' => 80,
    'Hair Treatment' => 70,
    'Cleanup' => 45
];

include_once("../../includes/notifications.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_bill'])) {
    $appid = $_POST['appointment_id'];
    $amount = $_POST['b_amount'];
    // get client_id, staff_id, emails from appointment/clients/staff/users - Using LEFT JOIN in case stylist was removed
    $q = mysqli_query($con, "SELECT a.client_id, a.staff_id, c.email as c_email, c.name as c_name, a.service, u.email as s_email, u.id as s_user_id 
                             FROM appointments a 
                             JOIN clients c ON a.client_id = c.client_id 
                             LEFT JOIN staff s ON a.staff_id = s.staff_id
                             LEFT JOIN users u ON s.user_id = u.id
                             WHERE a.appointment_id = " . intval($appid));
    if($nData = mysqli_fetch_assoc($q)) {
        $cid = $nData['client_id'];
        $sid = $nData['staff_id'];
        $s_uid = $nData['s_user_id'];
        
        // Security: Ensure staff can only bill their own appointments
        if ($sid != $sid_current) {
            echo '<script>Swal.fire("Access Denied", "You can only generate bills for your own appointments.", "error")</script>';
        } else {
            $ins = mysqli_prepare($con, "INSERT INTO billing (client_id, appointment_id, amount, date) VALUES (?, ?, ?, CURDATE())");
            mysqli_stmt_bind_param($ins, "iid", $cid, $appid, $amount);
            $bsuccess = mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            // mark appointment completed (if not already)
            mysqli_query($con, "UPDATE appointments SET status='completed' WHERE appointment_id = " . intval($appid));
            
            if($bsuccess) {
                include_once("../../includes/notifications.php");
                // Notify Customer
                sendNotification($con, $nData['c_email'], "Payment of $$amount for your {$nData['service']} has been received. Thank you!", 'email', $cid);
                // Notify Staff (Using User ID for inbox visibility)
                if(!empty($s_uid)) {
                    sendNotification($con, $nData['s_email'], "Payment Confirmed: $$amount for {$nData['service']} with {$nData['c_name']}.", 'email', $s_uid);
                }
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
                <h3>Billing & Checkout</h3>
                <p class="text-muted mb-0">Generate receipts and manage payments.</p>
            </div>
        </div>

        <div class="glass-card">
            <?php if(isset($bsuccess) && $bsuccess) echo '<div class="alert alert-success">Invoice Generated and Payment Recorded!</div>'; ?>
            <form action="" method="post">
                <div class="mb-3">
                    <label class="form-label">Select Finished Appointment</label>
                    <select name="appointment_id" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        <?php
                        $target_app = isset($_GET['app_id']) ? intval($_GET['app_id']) : 0;
                        
                        $st = mysqli_query($con, "SELECT a.appointment_id, c.name, a.service 
                                                  FROM appointments a 
                                                  JOIN clients c ON a.client_id = c.client_id 
                                                  LEFT JOIN billing b ON a.appointment_id = b.appointment_id 
                                                  WHERE (a.status = 'completed' AND b.bill_id IS NULL AND a.staff_id = $sid_current)
                                                  OR a.appointment_id = $target_app
                                                  ORDER BY a.date DESC LIMIT 50");
                        if(mysqli_num_rows($st) > 0) {
                            echo "<option value=''>-- Select Appointment --</option>";
                            while($s = mysqli_fetch_assoc($st)) {
                                $p = isset($prices[$s['service']]) ? $prices[$s['service']] : 0;
                                $sel = ($s['appointment_id'] == $target_app) ? "selected" : "";
                                echo "<option value='{$s['appointment_id']}' data-price='{$p}' {$sel}>{$s['name']} - {$s['service']}</option>";
                            }
                        } else {
                            echo "<option value=''>No pending completions found for you</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount Charged ($)</label>
                    <input type="number" step="0.01" id="amountField" name="b_amount" class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                </div>
                <button type="submit" name="generate_bill" class="btn-gold mt-3">Generate Invoice</button>
            </form>
        </div>

        <div class="glass-card mt-4">
            <h5 class="text-gold mb-3">Recent Transactions</h5>
            <table class="table table-dark table-hover border-light mb-0" style="background: transparent;">
                <thead><tr><th class="text-gold">Bill ID</th><th class="text-gold">Client</th><th class="text-gold">Amount</th><th class="text-gold">Date</th><th class="text-gold">Action</th></tr></thead>
                <tbody>
                    <?php
                    $res = mysqli_query($con, "SELECT b.bill_id, c.name, b.amount, b.date FROM billing b JOIN clients c ON b.client_id = c.client_id WHERE b.date = CURDATE() ORDER BY b.bill_id DESC");
                    while($row = mysqli_fetch_assoc($res)) {
                        echo "<tr>
                            <td>#INV-{$row['bill_id']}</td>
                            <td>{$row['name']}</td>
                            <td>$" . number_format($row['amount'], 2) . "</td>
                            <td>{$row['date']}</td>
                            <td><a href='invoice.php?id={$row['bill_id']}' target='_blank' class='text-gold small'>Print Receipt</a></td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function updatePrice() {
    const select = document.querySelector('select[name="appointment_id"]');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    if (price) {
        document.getElementById('amountField').value = price;
    } else {
        document.getElementById('amountField').value = '';
    }
}

document.querySelector('select[name="appointment_id"]').addEventListener('change', updatePrice);

// Run on load to handle pre-selected app_id
window.addEventListener('load', updatePrice);
</script>
<?php include_once("../../footer.php"); ?>
