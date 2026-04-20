<?php
/**
 * Elegant Salon Automation Cleanup
 * This script automatically transitions past pending appointments to 'completed'.
 * Include this in dashboard headers for "Lazy Automation".
 */

include_once(__DIR__ . "/../db.php");

$cleanupQuery = "UPDATE appointments 
                 SET status = 'completed' 
                 WHERE status = 'pending' 
                 AND (
                     date < CURDATE() 
                     OR (date = CURDATE() AND time < CURTIME())
                 )";

mysqli_query($con, $cleanupQuery);
?>
