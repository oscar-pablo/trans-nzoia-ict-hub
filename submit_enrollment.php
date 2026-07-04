<?php
/**
 * submit_enrollment.php
 * Handles student enrollment form submissions via JSON POST.
 * Validates fields and saves record into the enrollments table.
 */

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get the raw POST data
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or empty request payload.'
    ]);
    exit;
}

// Extract and sanitize input data
$firstName  = trim($inputData['first_name'] ?? '');
$middleName = trim($inputData['middle_name'] ?? '');
$lastName   = trim($inputData['last_name'] ?? '');
$hasId      = isset($inputData['has_id']) ? (int)$inputData['has_id'] : 1;
$idType     = trim($inputData['id_type'] ?? '');
$idNumber   = trim($inputData['id_number'] ?? '');
$phone      = trim($inputData['phone'] ?? '');
$email      = trim($inputData['email'] ?? '');
$address    = trim($inputData['address'] ?? '');
$course     = trim($inputData['course'] ?? '');
$schedule   = trim($inputData['schedule'] ?? '');

// Server-side validation
if (empty($firstName)) {
    echo json_encode(['status' => 'error', 'message' => 'First name is required.']);
    exit;
}
if (empty($lastName)) {
    echo json_encode(['status' => 'error', 'message' => 'Last name is required.']);
    exit;
}
if ($hasId === 1) {
    if (empty($idType)) {
        echo json_encode(['status' => 'error', 'message' => 'Please select identification type.']);
        exit;
    }
    if (empty($idNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter your ID or Certificate number.']);
        exit;
    }
} else {
    // If the student doesn't have an ID, ignore any fields passed
    $idType   = null;
    $idNumber = null;
}
if (empty($phone)) {
    echo json_encode(['status' => 'error', 'message' => 'Active phone number is required.']);
    exit;
}
if (empty($address)) {
    echo json_encode(['status' => 'error', 'message' => 'Residential address is required.']);
    exit;
}
if (empty($course)) {
    echo json_encode(['status' => 'error', 'message' => 'Course of interest selection is required.']);
    exit;
}

// Connect to database and insert enrollment
require_once 'page_db.php';

try {
    $sql = "INSERT INTO enrollments (first_name, middle_name, last_name, has_id, id_type, id_number, phone, email, address, course, schedule, status) 
            VALUES (:first_name, :middle_name, :last_name, :has_id, :id_type, :id_number, :phone, :email, :address, :course, :schedule, 'Pending')";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name'  => $firstName,
        ':middle_name' => empty($middleName) ? null : $middleName,
        ':last_name'   => $lastName,
        ':has_id'      => $hasId,
        ':id_type'     => $idType,
        ':id_number'   => $idNumber,
        ':phone'       => $phone,
        ':email'       => empty($email) ? null : $email,
        ':address'     => $address,
        ':course'      => $course,
        ':schedule'    => empty($schedule) ? null : $schedule
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Thank you for applying. We will contact you within 24 hours to confirm your enrollment.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error occurred: ' . $e->getMessage()
    ]);
}
?>
