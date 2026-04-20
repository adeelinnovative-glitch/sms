<?php
include_once("db.php");
$res = mysqli_query($con, "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
