<?php
include 'db.php';
// Add columns to inventory
mysqli_query($con, "ALTER TABLE inventory ADD COLUMN last_po_date DATE DEFAULT NULL");
mysqli_query($con, "ALTER TABLE inventory ADD COLUMN po_status ENUM('none','sent') DEFAULT 'none'");

// Add is_hidden to notifications
mysqli_query($con, "ALTER TABLE notifications ADD COLUMN is_hidden TINYINT DEFAULT 0");

echo "DB Upgrade Success";
?>
