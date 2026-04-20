<?php
include 'db.php';
// Add column
mysqli_query($con, "ALTER TABLE staff ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");

// Get Admin Email
$res = mysqli_query($con, "SELECT email FROM users WHERE role = 'admin' LIMIT 1");
$row = mysqli_fetch_assoc($res);
echo "ADMIN_EMAIL:" . ($row['email'] ?? 'admin@elegancesalon.com');
?>
