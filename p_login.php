<?php
/**
 * p_login.php
 * Handles the admin login form submitted from page_admin-login.php.
 *
 * ── Assumption ───────────────────────────────────────────────────────────
 * This expects a table called `admins` with (at least) these columns:
 *   id        INT / PK
 *   username  VARCHAR
 *   password  VARCHAR   -- hashed with PHP's password_hash(), NOT plain text
 *
 * If your table/column names are different, or your existing passwords are
 * stored as plain text rather than hashed, tell me and I'll adjust this file
 * to match your actual schema.
 *
 * If you need to create your first admin account / a hashed password, see
 * the note at the bottom of this file.
 */
session_start();

require_once 'page_db.php';

// ── Only accept POST requests here ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: page_admin-login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// ── Basic validation ─────────────────────────────────────────────────────
if ($username === '' || $password === '') {
    header("Location: page_admin-login.php?error=empty&username=" . urlencode($username));
    exit;
}

// ── Look up the admin by username ───────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Login check failed: " . $e->getMessage());
}

// ── Verify the password against the stored hash ─────────────────────────
if ($admin && password_verify($password, $admin['password'])) {
    // Regenerate the session ID on login to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['logged_in']  = true;
    $_SESSION['admin_name'] = $admin['username'];

    header("Location: page_admin_dashboard.php");
    exit;
} else {
    header("Location: page_admin-login.php?error=invalid&username=" . urlencode($username));
    exit;
}

/**
 * ── Creating an admin account ───────────────────────────────────────────
 * If you don't have an `admins` table yet, or need to add your first
 * account, here's the table and a one-off way to generate a password hash.
 *
 * SQL to create the table:
 *
 *   CREATE TABLE admins (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     username VARCHAR(100) NOT NULL UNIQUE,
 *     password VARCHAR(255) NOT NULL,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 *   );
 *
 * To generate a hashed password to insert, run this in a throwaway PHP file
 * (delete it afterwards!) or in `php -a` interactive mode:
 *
 *   echo password_hash('yourChosenPassword', PASSWORD_DEFAULT);
 *
 * Then insert the result into the admins table:
 *
 *   INSERT INTO admins (username, password) VALUES ('yourUsername', 'PASTE_THE_HASH_HERE');
 */