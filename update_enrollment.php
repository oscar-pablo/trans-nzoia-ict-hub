<?php
/**
 * update_enrollment.php
 * Handles admin AJAX updates (approve, reject, delete) for student enrollments.
 * Secured via session check. Sends notification emails to students on
 * approval or rejection, when an email address is on file.
 */

header('Content-Type: application/json');
session_start();

// Security check: Only logged-in admin users can call this script
if (!isset($_SESSION['admin_id']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized access. Please log in.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Read input (JSON payload)
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData) {
    $inputData = $_POST;
}

$id     = isset($inputData['id']) ? (int)$inputData['id'] : 0;
$action = trim($inputData['action'] ?? '');

if ($id <= 0 || empty($action)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing enrollment ID or action.'
    ]);
    exit;
}

require_once __DIR__ . '/page_db.php';

try {
    // Check if enrollment exists — now also fetching email and course,
    // needed to send approval/rejection notification emails.
    $checkStmt = $pdo->prepare("SELECT id, first_name, last_name, email, course FROM enrollments WHERE id = ?");
    $checkStmt->execute([$id]);
    $enrollment = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Enrollment record not found.'
        ]);
        exit;
    }

    $studentName  = htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']);
    $studentFirst = $enrollment['first_name'];
    $studentEmail = $enrollment['email'] ?? '';
    $studentCourse = $enrollment['course'] ?? '';

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'Approved' WHERE id = ?");
        $stmt->execute([$id]);

        // ── Notify the student, if we have an email on file ──────────────────
        $emailResult = ['sent' => false, 'error' => 'No email provided'];
        if (!empty($studentEmail)) {
            require_once __DIR__ . '/mail_enrollment.php';
            $emailResult = sendApprovalEmail($studentFirst, $studentEmail, $studentCourse);
        }

        $emailNote = (!empty($studentEmail) && $emailResult['sent'])
            ? " A notification email has been sent to {$studentEmail}."
            : '';

        echo json_encode([
            'status' => 'success',
            'message' => "Application for {$studentName} has been approved successfully.{$emailNote}"
        ]);
        exit;

    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE enrollments SET status = 'Rejected' WHERE id = ?");
        $stmt->execute([$id]);

        // ── Notify the student, if we have an email on file ──────────────────
        $emailResult = ['sent' => false, 'error' => 'No email provided'];
        if (!empty($studentEmail)) {
            require_once __DIR__ . '/mail_enrollment.php';
            $emailResult = sendRejectionEmail($studentFirst, $studentEmail, $studentCourse);
        }

        $emailNote = (!empty($studentEmail) && $emailResult['sent'])
            ? " A notification email has been sent to {$studentEmail}."
            : '';

        echo json_encode([
            'status' => 'success',
            'message' => "Application for {$studentName} has been rejected.{$emailNote}"
        ]);
        exit;

    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM enrollments WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'status' => 'success',
            'message' => "Enrollment record for {$studentName} has been permanently deleted."
        ]);
        exit;

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action requested.'
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
