<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Robust authentication check.
 * Verifies that the logged-in user still exists in the database.
 * If the user has been deleted (e.g. by an admin), they are logged out.
 */
if (isset($_SESSION['id'])) {
    // Relative path to db.php from this file
    require_once(__DIR__ . "/../db.php");
    
    $uid = intval($_SESSION['id']);
    $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $uid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        // User record no longer exists - Force logout
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        
        // Redirect to landing page
        header("Location: /eproject/index.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}
?>
