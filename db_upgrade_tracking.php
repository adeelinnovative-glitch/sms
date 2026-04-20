<?php
include 'db.php';
mysqli_query($con, "ALTER TABLE staff ADD COLUMN is_profile_updated TINYINT DEFAULT 0, ADD COLUMN updated_fields TEXT DEFAULT NULL");
echo "Column added successfully";
?>
