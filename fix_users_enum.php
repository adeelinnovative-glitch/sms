<?php
include_once("db.php");
$res = mysqli_query($con, "ALTER TABLE users MODIFY COLUMN role ENUM('admin','receptionist','stylist','beautician','customer')");
if($res) echo "SUCCESS"; else echo "FAILED: " . mysqli_error($con);
?>
