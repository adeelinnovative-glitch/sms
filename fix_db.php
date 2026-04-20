<?php
include_once("db.php");

$query = "ALTER TABLE users MODIFY password VARCHAR(255)";
if (mysqli_query($con, $query)) {
    echo "Password column successfully updated to VARCHAR(255) to support modern hashing!";
} else {
    echo "Error updating table: " . mysqli_error($con);
}
?>
