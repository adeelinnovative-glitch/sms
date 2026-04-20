<?php
include_once("db.php");
$res = mysqli_query($con, "ALTER TABLE staff ADD COLUMN time_slot VARCHAR(50) AFTER schedule");
if($res) echo "SUCCESS"; else echo "FAILED: " . mysqli_error($con);
?>
