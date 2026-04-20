<?php
include_once("db.php");
$res = mysqli_query($con, "DESCRIBE appointments");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " (" . $row['Type'] . ") | Null: " . $row['Null'] . "\n";
}
?>
