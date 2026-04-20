<?php
include_once("db.php");
echo "STAFF TABLE:\n";
$res = mysqli_query($con, "DESCRIBE staff");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
?>
