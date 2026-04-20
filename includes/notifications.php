<?php
/**
 * Elegant Salon Notification Helper
 * This helper provides a unified way to send automated notifications (Email/SMS).
 * In a production environment, this would integrate with PHPMailer or Twilio.
 * For this eProject, it logs notifications to the database for audit.
 */

function sendNotification($con, $recipient, $message, $type = 'email', $userId = null) {
    $recipient = mysqli_real_escape_string($con, $recipient);
    $message = mysqli_real_escape_string($con, $message);
    $userIdVal = $userId ? intval($userId) : "NULL";
    
    $query = "INSERT INTO notifications (user_id, type, recipient, message, status) 
              VALUES ($userIdVal, '$type', '$recipient', '$message', 'sent')";
    
    return mysqli_query($con, $query);
}

// Example usage:
// sendNotification($con, 'client@example.com', 'Your appointment is confirmed!', 'email', $clientId);
?>
