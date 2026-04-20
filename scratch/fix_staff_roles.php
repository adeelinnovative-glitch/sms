<?php
include_once("db.php");

echo "Removing 'receptionist' from staff role enum...\n";

$sql = "ALTER TABLE staff MODIFY COLUMN role ENUM('', 'stylist', 'beautician', 'nail technician')";
if (mysqli_query($con, $sql)) {
    echo "Migration successful.\n";
    
    // Also update any existing staff who might have been receptionists to empty or default
    // (Assuming no critical data loss is acceptable as per user request to remove the role)
    mysqli_query($con, "UPDATE staff SET role = '' WHERE role = 'receptionist'");
    echo "Existing records updated.\n";
} else {
    echo "Error: " . mysqli_error($con) . "\n";
}
?>
