<?php
// logout.php
// Destroy the admin session and redirect to login page
session_start();
// Unset all session variables
$_SESSION = array();
// Delete the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
// Destroy the session
session_destroy();
// Prevent caching of this page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
// Redirect to login page
header('Location: page_admin-login.php');
exit;
?>
