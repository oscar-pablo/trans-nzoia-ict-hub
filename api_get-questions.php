<?php
/**
 * api_get-questions.php
 * Endpoint to retrieve security questions for a given admin email.
 */
header('Content-Type: application/json');
require_once 'page_db.php';

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT security_question_1, security_question_2, security_question_3 FROM admins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        if (empty($admin['security_question_1'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Security questions have not been set for this account. Please contact the database administrator.'
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'question_1' => $admin['security_question_1'],
                'question_2' => $admin['security_question_2'],
                'question_3' => $admin['security_question_3']
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No administrator account found with that email address.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
