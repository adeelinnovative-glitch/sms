<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}
include_once("../../db.php");

if(!isset($_GET['id'])) {
    die("PO ID Missing.");
}

$id = intval($_GET['id']);
$res = mysqli_query($con, "SELECT * FROM inventory WHERE item_id = $id");
$row = mysqli_fetch_assoc($res);

if(!$row) die("Item not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order - #PO-<?= $id ?><?= date('is') ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #333; padding: 40px; }
        .po-header { border-bottom: 2px solid #daa520; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .po-title { color: #daa520; font-size: 2rem; font-weight: bold; }
        .po-details { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .company-info { width: 45%; }
        .supplier-info { width: 45%; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #f8f8f8; border-bottom: 1px solid #ddd; padding: 12px; text-align: left; color: #555; }
        td { border-bottom: 1px solid #f0f0f0; padding: 12px; }
        .total-section { margin-top: 30px; border-top: 2px solid #eee; padding-top: 20px; text-align: right; }
        .footer { margin-top: 50px; text-align: center; color: #999; font-size: 0.85rem; border-top: 1px solid #eee; padding-top: 20px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="if(window.location.search.includes('print=1')) window.print()">

<div class="no-print" style="margin-bottom: 20px; text-align: right;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #daa520; color: #fff; border: none; cursor: pointer;">Print PO</button>
</div>

<div class="po-header">
    <div class="po-title">ELEGANCE SALON</div>
    <div>
        <strong>PURCHASE ORDER</strong><br>
        #PO-<?= $id ?><?= date('is') ?><br>
        Date: <?= $row['last_po_date'] ?: date('Y-m-d') ?>
    </div>
</div>

<div class="po-details">
    <div class="company-info">
        <strong>SHIP TO:</strong><br>
        Elegance Salon HQ<br>
        123 Luxury Avenue<br>
        Beverly Hills, CA 90210<br>
        Phone: +1 (555) 123-4567
    </div>
    <div class="supplier-info">
        <strong>SUPPLIER:</strong><br>
        <?= htmlspecialchars($row['supplier_info']) ?>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Item Description</th>
            <th>Proposed Qty</th>
            <th>Estimated Unit Price</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?= htmlspecialchars($row['product_name']) ?> (Restock Order)</td>
            <td>50 Units</td>
            <td>$<?= number_format($row['price'], 2) ?></td>
            <td>$<?= number_format($row['price'] * 50, 2) ?></td>
        </tr>
    </tbody>
</table>

<div class="total-section">
    <div style="font-size: 1.2rem;"><strong>Estimated Total: $<?= number_format($row['price'] * 50, 2) ?></strong></div>
</div>

<div class="footer">
    Authorized by Elegance Salon Administration. Generated via Command Center.<br>
    <em>This is a computer-generated document. No signature required.</em>
</div>

</body>
</html>
