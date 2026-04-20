<?php
include 'db.php';
$res = mysqli_query($con, "SHOW COLUMNS FROM appointments");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
$res2 = mysqli_query($con, "SHOW COLUMNS FROM billing");
while($row = mysqli_fetch_assoc($res2)) {
    print_r($row);
}
?>
