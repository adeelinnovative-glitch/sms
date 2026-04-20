<?php
include 'db.php';
echo "--- STAFF TABLE ---\n";
$res = mysqli_query($con, "SHOW COLUMNS FROM staff");
while($row = mysqli_fetch_assoc($res)) { print_r($row); }
echo "\n--- USERS TABLE ---\n";
$res2 = mysqli_query($con, "SHOW COLUMNS FROM users");
while($row = mysqli_fetch_assoc($res2)) { print_r($row); }
?>
