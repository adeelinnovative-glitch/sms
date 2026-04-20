<?php
include_once("db.php");
// 1. Drop the existing foreign key (to allow modifying the column)
mysqli_query($con, "ALTER TABLE appointments DROP FOREIGN KEY appointments_ibfk_1");

// 2. Modify the column to allow NULL
mysqli_query($con, "ALTER TABLE appointments MODIFY COLUMN staff_id INT(11) NULL");

// 3. Add the foreign key back with SET NULL on delete behavior
$res = mysqli_query($con, "ALTER TABLE appointments ADD CONSTRAINT appointments_ibfk_1 FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON DELETE SET NULL");

if($res) echo "SUCCESS"; else echo "FAILED: " . mysqli_error($con);
?>
