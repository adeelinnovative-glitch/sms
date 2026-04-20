<?php
include_once("db.php");
$res = mysqli_query($con, "DESCRIBE staff");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>
