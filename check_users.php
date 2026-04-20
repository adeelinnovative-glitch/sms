<?php
include_once("db.php");
$res = mysqli_query($con, "SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Email: " . $row['email'] . " | Role: [" . $row['role'] . "]\n";
}
?>
