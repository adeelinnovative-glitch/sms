<?php
include_once("db.php");
$res = mysqli_query($con, "SHOW COLUMNS FROM staff LIKE 'role'");
$row = mysqli_fetch_assoc($res);
echo "Role Type: " . $row['Type'] . "\n";
?>
