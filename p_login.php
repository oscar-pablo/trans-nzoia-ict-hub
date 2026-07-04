<?php
session_start();
require_once 'page_db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: page_admin-login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Empty field check
if (empty($username) || empty($password)) {
    header("Location: page_admin-login.php?error=empty");
    exit;
}

// Fetch the user from admin_users table
$stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $storedPassword   = $user['password'];
    $isPasswordCorrect = false;

    // Check if the stored password is already a bcrypt hash
    if (password_get_info($storedPassword)['algo'] !== 0) {
        // Already hashed — use secure verification
        $isPasswordCorrect = password_verify($password, $storedPassword);
    } else {
        // Still plain text — direct comparison (fallback)
        $isPasswordCorrect = ($password === $storedPassword);

        // Auto-upgrade: silently hash the plain-text password on first successful login
        if ($isPasswordCorrect) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $update  = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $update->execute([$newHash, $user['id']]);
        }
    }

    if ($isPasswordCorrect) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['admin_id']   = $user['id'];
        $_SESSION['admin_name'] = $user['username'];
        $_SESSION['logged_in']  = true;

        header("Location: page_admin_dashboard.php");
        exit;
    }
}

// Login failed — keep error message generic (don't reveal if username exists)
header("Location: page_admin-login.php?error=invalid&username=" . urlencode($username));
exit;
?>
