<?php
include_once("../../includes/auth_check.php");
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'receptionist' && $_SESSION["role"] !== 'stylist' && $_SESSION["role"] !== 'beautician')) {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

if(!isset($_GET['id'])) {
    die("Invoice ID required.");
}

$id = intval($_GET['id']);
$res = mysqli_query($con, "SELECT b.*, c.name as client_name, c.email as client_email, a.service, s.name as stylist_name 
                           FROM billing b 
                           JOIN clients c ON b.client_id = c.client_id 
                           JOIN appointments a ON b.appointment_id = a.appointment_id
                           LEFT JOIN staff s ON a.staff_id = s.staff_id
                           WHERE b.bill_id = $id");

$data = mysqli_fetch_assoc($res);
if(!$data) die("Invoice not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #INV-<?php echo $id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #000; padding: 50px; }
        .invoice-box { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #daa520; padding-bottom: 20px; margin-bottom: 30px; }
        .text-gold { color: #daa520 !important; }
        @media print {
            .btn-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="invoice-box">
    <div class="text-end">
        <button onclick="window.print()" class="btn btn-dark btn-print">Print Receipt</button>
    </div>
    
    <div class="header mt-4">
        <div>
            <h1 class="text-gold">ELEGANCE SALON</h1>
            <p>123 Luxury Avenue, Beverly Hills, CA 90210<br>+1 (555) 123-4567<br>contact@elegancesalon.com</p>
        </div>
        <div class="text-end">
            <h2>INVOICE</h2>
            <p><strong>#INV-<?php echo $id; ?></strong><br>Date: <?php echo $data['date']; ?></p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <h5 class="text-gold">Billed To:</h5>
            <p><strong><?php echo $data['client_name']; ?></strong><br><?php echo $data['client_email']; ?></p>
        </div>
        <div class="col-6 text-end">
            <h5 class="text-gold">Stylist:</h5>
            <p><?php echo $data['stylist_name'] ?: 'Removed Stylist'; ?></p>
        </div>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Service Description</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo $data['service']; ?></td>
                <td class="text-end">$<?php echo number_format($data['amount'], 2); ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th class="text-end">TOTAL</th>
                <th class="text-end">$<?php echo number_format($data['amount'], 2); ?></th>
            </tr>
        </tfoot>
    </table>

    <div class="mt-5 text-center">
        <p class="text-muted">Thank you for choosing Elegance Salon. We hope to see you again soon!</p>
    </div>
</div>

</body>
</html>
