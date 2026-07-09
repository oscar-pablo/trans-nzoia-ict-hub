<?php
/**
 * api_update-profile.php
 * Secure endpoint to update the admin's email, password, and security questions.
 */
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['admin_id']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in again.']);
    exit;
}

require_once 'page_db.php';

$adminId = $_SESSION['admin_id'];

// Get POST variables
$currentPassword = $_POST['current_password'] ?? '';
$email           = trim($_POST['email'] ?? '');
$q1              = trim($_POST['q1'] ?? '');
$a1              = trim($_POST['a1'] ?? '');
$q2              = trim($_POST['q2'] ?? '');
$a2              = trim($_POST['a2'] ?? '');
$q3              = trim($_POST['q3'] ?? '');
$a3              = trim($_POST['a3'] ?? '');
$newPassword     = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword)) {
    echo json_encode(['status' => 'error', 'message' => 'Current password is required to verify your identity.']);
    exit;
}

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email address is required.']);
    exit;
}

// If they want to change password, validate it
if (!empty($newPassword)) {
    if ($newPassword !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
        exit;
    }
    if (strlen($newPassword) < 6) {
        echo json_encode(['status' => 'error', 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
}

try {
    // 1. Fetch current admin details
    $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect current password.']);
        exit;
    }

    // 2. Prepare the update query
    $sql = "UPDATE admins SET email = :email";
    $params = [
        ':email' => $email,
        ':id'    => $adminId
    ];

    // Update security question 1
    if (!empty($q1) && !empty($a1)) {
        $sql .= ", security_question_1 = :q1, security_answer_1 = :a1";
        $params[':q1'] = $q1;
        $params[':a1'] = password_hash(strtolower($a1), PASSWORD_DEFAULT);
    }
    
    // Update security question 2
    if (!empty($q2) && !empty($a2)) {
        $sql .= ", security_question_2 = :q2, security_answer_2 = :a2";
        $params[':q2'] = $q2;
        $params[':a2'] = password_hash(strtolower($a2), PASSWORD_DEFAULT);
    }

    // Update security question 3
    if (!empty($q3) && !empty($a3)) {
        $sql .= ", security_question_3 = :q3, security_answer_3 = :a3";
        $params[':q3'] = $q3;
        $params[':a3'] = password_hash(strtolower($a3), PASSWORD_DEFAULT);
    }

    // Update password if a new one is set
    if (!empty($newPassword)) {
        $sql .= ", password = :new_password";
        $params[':new_password'] = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = :id";

    $updateStmt = $pdo->prepare($sql);
    $updateStmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Profile and security settings updated successfully.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
