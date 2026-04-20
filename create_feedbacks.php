<?php
include_once("db.php");
$query = "CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    message TEXT,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if(mysqli_query($con, $query)){
    echo "Table created successfully.";
} else {
    echo "Error: " . mysqli_error($con);
}
?>
