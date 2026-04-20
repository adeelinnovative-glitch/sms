<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/../db.php");

$response = ['valid' => false];

if (isset($_SESSION['id'])) {
    $uid = intval($_SESSION['id']);
    $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $response['valid'] = true;
    } else {
        // Not valid anymore, clean up session
        $_SESSION = array();
        session_destroy();
    }
    mysqli_stmt_close($stmt);
} else {
    // No session ID, but if they are on a dashboard, that might be intentional or already handled
    // However, for polling we return valid=false to trigger redirect if they are on a protected page
    $response['valid'] = false;
}

echo json_encode($response);
?>
