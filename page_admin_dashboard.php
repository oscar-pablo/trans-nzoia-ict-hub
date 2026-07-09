<?php
/**
 * page_admin_dashboard.php
 * Secure Administrator Dashboard.
 * Allows searching, filtering, status updates (Approve/Reject), and deletion of registrations.
 * Supports CSV export.
 */
session_start();

// Security: Redirect unauthorized users back to login
if (!isset($_SESSION['admin_id']) || $_SESSION['logged_in'] !== true) {
    header("Location: page_admin-login.php");
    exit;
}

require_once 'page_db.php';

// ── CSV EXPORT ──────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Retrieve current filters to export the filtered set
    $search   = trim($_GET['search'] ?? '');
    $schedule = trim($_GET['schedule'] ?? '');

    $sql = "SELECT id, first_name, middle_name, last_name, has_id, id_type, id_number, phone, email, address, course, schedule, status, created_at FROM enrollments WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR phone LIKE :search OR id_number LIKE :search OR email LIKE :search OR address LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($schedule !== '') {
        $sql .= " AND schedule = :schedule";
        $params[':schedule'] = $schedule;
    }

    $sql .= " ORDER BY id DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Force download header
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=TransNzoia_ICT_Hub_Enrollments_' . date('Y-m-d_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'ID', 'First Name', 'Middle Name', 'Last Name', 'Has ID?', 'ID Type', 'ID/Cert Number', 
            'Phone', 'Email', 'Address', 'Course', 'Schedule', 'Status', 'Date Registered'
        ]);

        foreach ($records as $row) {
            fputcsv($output, [
                $row['id'],
                $row['first_name'],
                $row['middle_name'],
                $row['last_name'],
                $row['has_id'] ? 'Yes' : 'No',
                $row['id_type'],
                $row['id_number'],
                $row['phone'],
                $row['email'],
                $row['address'],
                $row['course'],
                $row['schedule'],
                $row['status'],
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;

    } catch (PDOException $e) {
        die("Export failed: " . $e->getMessage());
    }
}

// ── GET STATISTICS ────────────────────────────────────────────────────────
try {
    // Total Count
    $totalCount = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
    
    // Pending Count
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Pending'")->fetchColumn();
    
    // Approved Count
    $approvedCount = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Approved'")->fetchColumn();
    
    // Rejected Count
    $rejectedCount = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Rejected'")->fetchColumn();

    // Course counts for sidebar widget
    $courseStatsStmt = $pdo->query("SELECT course, COUNT(*) as count FROM enrollments GROUP BY course ORDER BY count DESC");
    $courseStats = $courseStatsStmt->fetchAll(PDO::FETCH_ASSOC);
    // Fetch logged-in admin details for security profile
    $adminProfileStmt = $pdo->prepare("SELECT email, security_question_1, security_question_2, security_question_3 FROM admins WHERE id = :id LIMIT 1");
    $adminProfileStmt->execute([':id' => $_SESSION['admin_id']]);
    $adminProfile = $adminProfileStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database stats load failed: " . $e->getMessage());
}

// ── DYNAMIC FILTERING & SEARCH ────────────────────────────────────────────
$searchFilter   = trim($_GET['search'] ?? '');
$scheduleFilter = trim($_GET['schedule'] ?? '');

$sql = "SELECT * FROM enrollments WHERE 1=1";
$params = [];

if ($searchFilter !== '') {
    $sql .= " AND (first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR phone LIKE :search OR id_number LIKE :search OR email LIKE :search OR address LIKE :search)";
    $params[':search'] = "%$searchFilter%";
}
if ($scheduleFilter !== '') {
    $sql .= " AND schedule = :schedule";
    $params[':schedule'] = $scheduleFilter;
}

$sql .= " ORDER BY id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database records load failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — Trans-Nzoia Community ICT Hub</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* ── RESET & TOKENS ────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    
    :root {
      --navy:         #003049;
      --navy-dark:    #001f30;
      --navy-mid:     #004060;
      --red:          #D62828;
      --red-hover:    #bf2020;
      --orange:       #F77F00;
      --amber:        #FCBF49;
      --amber-pale:   #FFF5D6;
      --text:         #1A1A1A;
      --text-light:   #E0E6ED;
      --muted:        #5C5040;
      --muted-light:  #8b9ba8;
      --border:       #2c3e50;
      --white:        #FFFFFF;
      
      /* Status colors */
      --green:        #2ecc71;
      --green-dark:   #27ae60;
      --green-bg:     rgba(46, 204, 113, 0.15);
      
      --orange-bg:    rgba(247, 127, 0, 0.15);
      
      --red-bg:       rgba(214, 40, 40, 0.15);
      
      --glass-bg:     rgba(255, 255, 255, 0.03);
      --glass-border: rgba(255, 255, 255, 0.08);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--navy-dark);
      color: var(--text-light);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; }

    /* ── HEADER ────────────────────────────────────────────────── */
    header {
      background: rgba(0, 30, 48, 0.85);
      backdrop-filter: blur(10px);
      border-bottom: 1px solid var(--glass-border);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo-badge {
      width: 52px; height: 52px;
      background: var(--red);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Poppins', sans-serif;
      font-weight: 800; color: var(--white);
      overflow: hidden;
      flex-shrink: 0;
      padding: 2px;
    }
    .logo-badge img {
      width: 100%; height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .logo-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--white);
      line-height: 1.2;
    }

    .logo-title small {
      display: block;
      font-size: 0.72rem;
      color: var(--amber);
      font-weight: 400;
    }

    .admin-profile {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .admin-name {
      font-size: 0.88rem;
      font-weight: 600;
    }
    
    .admin-name span {
      color: var(--amber);
    }

    .logout-btn {
      background: rgba(214, 40, 40, 0.15);
      border: 1px solid var(--red);
      color: #ff6b6b;
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 0.83rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .logout-btn:hover {
      background: var(--red);
      color: var(--white);
    }

    /* ── MAIN CONTENT LAYOUT ───────────────────────────────────── */
    .dashboard-container {
      max-width: 1400px;
      width: 100%;
      margin: 2rem auto;
      padding: 0 1.5rem;
      flex: 1;
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }

    /* ── STAT CARDS ────────────────────────────────────────────── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1.2rem;
    }

    .stat-card {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      transition: transform 0.2s;
    }
    
    .stat-card:hover {
      transform: translateY(-2px);
    }

    .stat-card::before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 4px;
      background: var(--amber);
    }

    .stat-card.total::before { background: var(--amber); }
    .stat-card.pending::before { background: var(--orange); }
    .stat-card.approved::before { background: var(--green); }
    .stat-card.rejected::before { background: var(--red); }

    .stat-label {
      font-size: 0.82rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted-light);
      margin-bottom: 5px;
    }

    .stat-value {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--white);
      line-height: 1.2;
    }

    /* ── FILTERS & CONTROLS ────────────────────────────────────── */
    .controls-card {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .filter-form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      align-items: flex-end;
    }

    .control-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .control-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--muted-light);
    }

    .input-field {
      background: rgba(0, 30, 48, 0.4);
      border: 1.5px solid var(--border);
      border-radius: 6px;
      padding: 9px 12px;
      font-family: inherit;
      color: var(--white);
      outline: none;
      font-size: 0.88rem;
      transition: border-color 0.2s;
    }

    .input-field:focus {
      border-color: var(--amber);
    }

    .input-field::placeholder {
      color: rgba(255, 255, 255, 0.25);
    }

    .button-group {
      display: flex;
      gap: 8px;
    }

    .btn {
      padding: 9px 16px;
      border-radius: 6px;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
    }

    .btn-primary {
      background: var(--amber);
      color: var(--navy-dark);
    }
    .btn-primary:hover {
      background: var(--white);
      box-shadow: 0 0 10px rgba(252, 191, 73, 0.3);
    }

    .btn-secondary {
      background: rgba(255,255,255,0.06);
      border: 1.5px solid var(--border);
      color: var(--text-light);
    }
    .btn-secondary:hover {
      background: rgba(255,255,255,0.1);
    }

    .btn-export {
      background: var(--green-dark);
      color: var(--white);
    }
    .btn-export:hover {
      background: var(--green);
    }

    .btn-export-pdf {
      background: var(--red);
      color: var(--white);
    }
    .btn-export-pdf:hover {
      background: var(--red-hover);
    }

    .btn-certificates {
      background: var(--navy-mid);
      border: 1.5px solid var(--amber);
      color: var(--amber);
    }
    .btn-certificates:hover {
      background: var(--amber);
      color: var(--navy-dark);
    }

    /* ── CERTIFICATE PRINT MODAL ───────────────────────────────── */
    .cert-mode-choice {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 1.2rem;
    }

    .cert-mode-card {
      background: rgba(0, 30, 48, 0.4);
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: 14px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
    }

    .cert-mode-card:hover {
      border-color: var(--amber);
    }

    .cert-mode-card.active {
      border-color: var(--amber);
      background: rgba(252, 191, 73, 0.1);
    }

    .cert-mode-card .cert-mode-icon {
      font-size: 1.6rem;
      margin-bottom: 6px;
    }

    .cert-mode-card .cert-mode-title {
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--white);
    }

    .cert-mode-card .cert-mode-sub {
      font-size: 0.75rem;
      color: var(--muted-light);
      margin-top: 3px;
    }

    .cert-student-list {
      max-height: 280px;
      overflow-y: auto;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      display: none;
    }

    .cert-student-list.visible {
      display: block;
    }

    .cert-student-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      border-bottom: 1px solid rgba(255,255,255,0.05);
      font-size: 0.85rem;
    }

    .cert-student-row:last-child {
      border-bottom: none;
    }

    .cert-student-row input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--amber);
      cursor: pointer;
      flex-shrink: 0;
    }

    .cert-student-name {
      font-weight: 600;
      color: var(--white);
    }

    .cert-student-meta {
      font-size: 0.72rem;
      color: var(--muted-light);
    }

    /* ── PDF FIELD-SELECTION MODAL ─────────────────────────────── */
    .pdf-fields-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 10px;
      margin: 1rem 0 1.4rem;
    }

    .pdf-field-option {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(0, 30, 48, 0.4);
      border: 1.5px solid var(--border);
      border-radius: 6px;
      padding: 9px 12px;
      cursor: pointer;
      font-size: 0.85rem;
      user-select: none;
      transition: border-color 0.2s;
    }

    .pdf-field-option:hover {
      border-color: var(--amber);
    }

    .pdf-field-option input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: var(--amber);
      cursor: pointer;
    }

    .pdf-select-toggle {
      background: none;
      border: none;
      color: var(--amber);
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: underline;
      padding: 0;
    }

    .pdf-modal-hint {
      font-size: 0.82rem;
      color: var(--muted-light);
      margin-bottom: 0.4rem;
    }

    /* ── DYNAMIC TABLE ─────────────────────────────────────────── */
    .table-card {
      background: var(--glass-bg);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .table-header {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--glass-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .table-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--white);
    }
    
    .table-count-tag {
      background: rgba(252, 191, 73, 0.12);
      border: 1px solid var(--amber);
      color: var(--amber);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 10px;
    }

    .table-wrapper {
      width: 100%;
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    th {
      background: rgba(0, 30, 48, 0.5);
      color: var(--muted-light);
      font-size: 0.78rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 12px 16px;
      border-bottom: 1px solid var(--glass-border);
    }

    td {
      padding: 14px 16px;
      border-bottom: 1px solid rgba(255,255,255,0.03);
      font-size: 0.86rem;
      vertical-align: middle;
    }

    tr:hover td {
      background: rgba(255,255,255,0.01);
    }

    /* Badges */
    .badge {
      display: inline-block;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
      text-align: center;
    }

    .badge-pending {
      background: var(--orange-bg);
      color: #ff9f43;
      border: 1px solid rgba(247, 127, 0, 0.3);
    }
    
    .badge-approved {
      background: var(--green-bg);
      color: #2ecc71;
      border: 1px solid rgba(46, 204, 113, 0.3);
    }

    .badge-rejected {
      background: var(--red-bg);
      color: #ff6b6b;
      border: 1px solid rgba(214, 40, 40, 0.3);
    }

    /* Action buttons in table */
    .actions-wrap {
      display: flex;
      gap: 5px;
    }

    .action-btn {
      width: 32px; height: 32px;
      border-radius: 6px;
      border: none;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.95rem;
      color: var(--white);
    }

    .act-view {
      background: rgba(255,255,255,0.06);
      border: 1px solid var(--border);
      color: var(--text-light);
    }
    .act-view:hover {
      background: rgba(255,255,255,0.15);
    }

    .act-approve {
      background: rgba(46, 204, 113, 0.15);
      border: 1px solid var(--green);
      color: var(--green);
    }
    .act-approve:hover {
      background: var(--green);
      color: var(--navy-dark);
    }

    .act-reject {
      background: rgba(247, 127, 0, 0.15);
      border: 1px solid var(--orange);
      color: var(--orange);
    }
    .act-reject:hover {
      background: var(--orange);
      color: var(--navy-dark);
    }

    .act-delete {
      background: rgba(214, 40, 40, 0.15);
      border: 1px solid var(--red);
      color: #ff6b6b;
    }
    .act-delete:hover {
      background: var(--red);
      color: var(--white);
    }

    .empty-state {
      text-align: center;
      padding: 3rem 2rem;
      color: var(--muted-light);
    }

    .empty-state-icon {
      font-size: 2.5rem;
      margin-bottom: 10px;
    }

    /* ── DETAIL MODAL ──────────────────────────────────────────── */
    .modal-overlay {
      display: none;
      position: fixed;
      left: 0; top: 0; right: 0; bottom: 0;
      background: rgba(0, 15, 24, 0.8);
      backdrop-filter: blur(8px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .modal-overlay.active {
      display: flex;
      opacity: 1;
    }

    .modal-card {
      background: #002b42;
      border: 1px solid var(--glass-border);
      border-radius: 16px;
      width: 100%;
      max-width: 600px;
      box-shadow: 0 25px 60px rgba(0,0,0,0.6);
      transform: scale(0.9);
      transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      overflow: hidden;
    }

    .modal-overlay.active .modal-card {
      transform: scale(1);
    }

    .modal-header {
      background: rgba(0, 30, 48, 0.5);
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid var(--glass-border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--white);
    }

    .modal-close {
      background: none;
      border: none;
      color: var(--muted-light);
      font-size: 1.5rem;
      cursor: pointer;
      transition: color 0.2s;
    }
    .modal-close:hover {
      color: var(--white);
    }

    .modal-body {
      padding: 1.5rem;
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.2rem;
    }

    .info-group {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    .info-group.full-width {
      grid-column: span 2;
    }

    .info-label {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--muted-light);
    }

    .info-value {
      font-size: 0.95rem;
      color: var(--white);
      font-weight: 500;
    }
    
    .info-warning {
      background: rgba(252, 191, 73, 0.12);
      border: 1px solid var(--amber);
      color: #ffeaa7;
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 0.8rem;
      grid-column: span 2;
      line-height: 1.4;
    }

    .modal-footer {
      background: rgba(0, 30, 48, 0.5);
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--glass-border);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    /* ── TOAST NOTIFICATIONS ────────────────────────────────────── */
    .toast-container {
      position: fixed;
      right: 20px;
      bottom: 20px;
      z-index: 2000;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .toast {
      background: rgba(0, 48, 73, 0.9);
      border: 1px solid var(--glass-border);
      color: var(--white);
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      font-size: 0.88rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      animation: slideIn 0.3s ease forwards;
      backdrop-filter: blur(10px);
    }
    
    .toast.success {
      border-left: 4px solid var(--green);
    }
    
    .toast.error {
      border-left: 4px solid var(--red);
    }

    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    .toast.fade-out {
      animation: fadeOut 0.3s ease forwards;
    }

    @keyframes fadeOut {
      to { transform: translateY(20px); opacity: 0; }
    }
  </style>
</head>
<body>

  <!-- ── HEADER ── -->
  <header>
    <div class="logo-container">
      <div class="logo-badge"><img src="data:image/png;base64,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" alt="Trans-Nzoia Community ICT Hub logo"></div>
      <div class="logo-title">
        Trans-Nzoia Community ICT Hub
        <small>Administration Dashboard</small>
      </div>
    </div>
    <div class="admin-profile">
      <div class="admin-name">Welcome, <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span></div>
      <button onclick="openSecurityModal()" class="logout-btn" style="background: rgba(252, 191, 73, 0.12); border-color: var(--amber); color: #ffeaa7;">Security Profile</button>
      <a href="index.html" class="logout-btn">Log Out</a>
    </div>
  </header>

  <!-- ── MAIN CONTAINER ── -->
  <div class="dashboard-container">

    <!-- ── STATS ROW ── -->
    <div class="stats-grid">
      <div class="stat-card total">
        <span class="stat-label">Total Enrollments</span>
        <span class="stat-value" id="stat-total"><?php echo $totalCount; ?></span>
      </div>
      <div class="stat-card pending">
        <span class="stat-label">Pending Reviews</span>
        <span class="stat-value" id="stat-pending"><?php echo $pendingCount; ?></span>
      </div>
      <div class="stat-card approved">
        <span class="stat-label">Approved Students</span>
        <span class="stat-value" id="stat-approved"><?php echo $approvedCount; ?></span>
      </div>
      <div class="stat-card rejected">
        <span class="stat-label">Rejected Reviews</span>
        <span class="stat-value" id="stat-rejected"><?php echo $rejectedCount; ?></span>
      </div>
    </div>

    <!-- ── FILTER CARD ── -->
    <div class="controls-card">
      <form method="GET" action="page_admin_dashboard.php" class="filter-form">
        
        <div class="control-group">
          <label class="control-label" for="search">Search Keywords</label>
          <input 
            class="input-field" 
            type="text" 
            id="search" 
            name="search" 
            placeholder="Name, Phone, ID or Village" 
            value="<?php echo htmlspecialchars($searchFilter); ?>"
          >
        </div>

        <div class="control-group">
          <label class="control-label" for="schedule">Schedule</label>
          <select class="input-field" id="schedule" name="schedule">
            <option value="">All Schedules</option>
            <option value="Morning (8am to 12pm)" <?php echo $scheduleFilter === 'Morning (8am to 12pm)' ? 'selected' : ''; ?>>Morning</option>
            <option value="Afternoon (1pm to 5pm)" <?php echo $scheduleFilter === 'Afternoon (1pm to 5pm)' ? 'selected' : ''; ?>>Afternoon</option>
            <option value="Evening (6pm to 9pm)" <?php echo $scheduleFilter === 'Evening (6pm to 9pm)' ? 'selected' : ''; ?>>Evening</option>
            <option value="Weekend Classes" <?php echo $scheduleFilter === 'Weekend Classes' ? 'selected' : ''; ?>>Weekend</option>
          </select>
        </div>

        <div class="button-group">
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="page_admin_dashboard.php" class="btn btn-secondary">Reset</a>
          <a 
            href="page_admin_dashboard.php?export=csv&search=<?php echo urlencode($searchFilter); ?>&schedule=<?php echo urlencode($scheduleFilter); ?>" 
            class="btn btn-export" 
            title="Download records as spreadsheet CSV"
          >
            Export CSV
          </a>
          <button 
            type="button"
            class="btn btn-export-pdf" 
            title="Choose fields and download as a printable PDF"
            onclick="openPdfModal()"
          >
            Export PDF
          </button>
          <button 
            type="button"
            class="btn btn-certificates" 
            title="Generate certificates and transcripts for students"
            onclick="openCertModal()"
          >
            Print Certificates
          </button>
        </div>
      </form>
    </div>

    <!-- ── TABLE CARD ── -->
    <div class="table-card">
      <div class="table-header">
        <h3 class="table-title">
          Applicant Registrations 
          <span class="table-count-tag" id="table-count"><?php echo count($enrollments); ?> matches</span>
        </h3>
      </div>
      <div class="table-wrapper">
        <table id="enrollments-table">
          <thead>
            <tr>
              <th style="width: 60px;">ID</th>
              <th>Applicant Name</th>
              <th>Course / Schedule</th>
              <th>Phone Number</th>
              <th>Location</th>
              <th>Identification</th>
              <th style="width: 110px;">Status</th>
              <th style="width: 160px; text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($enrollments)): ?>
              <tr>
                <td colspan="8">
                  <div class="empty-state">
                    <div class="empty-state-icon">📂</div>
                    <p>No enrollment applications found matching the filter criteria.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($enrollments as $student): ?>
                <tr id="row-<?php echo $student['id']; ?>">
                  <td><strong><?php echo $student['id']; ?></strong></td>
                  <td>
                    <div style="font-weight: 600; color: var(--white);">
                      <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--muted-light);">
                      <?php echo htmlspecialchars($student['email'] ? $student['email'] : 'No Email'); ?>
                    </div>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($student['course']); ?></div>
                    <div style="font-size: 0.75rem; color: var(--amber);">
                      <?php echo htmlspecialchars($student['schedule'] ? $student['schedule'] : 'Flexible Schedule'); ?>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($student['phone']); ?></td>
                  <td><?php echo htmlspecialchars($student['address']); ?></td>
                  <td>
                    <?php if ($student['has_id'] && !empty($student['id_number'])): ?>
                      <div style="font-weight: 500;"><?php echo htmlspecialchars($student['id_type']); ?></div>
                      <div style="font-size: 0.75rem; color: var(--muted-light);"><?php echo htmlspecialchars($student['id_number']); ?></div>
                    <?php else: ?>
                      <span style="color: #FCBF49; font-style: italic; font-size: 0.8rem;">⚠️ No ID Card</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge badge-<?php echo strtolower($student['status']); ?>" id="badge-<?php echo $student['id']; ?>">
                      <?php echo htmlspecialchars($student['status']); ?>
                    </span>
                  </td>
                  <td>
                    <div class="actions-wrap" style="justify-content: flex-end;">
                      <button 
                        class="action-btn act-view" 
                        title="View Full Profile Details"
                        onclick="viewStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                      >
                        👁️
                      </button>
                      
                      <!-- Conditionally hide or style status action triggers -->
                      <button 
                        class="action-btn act-approve" 
                        title="Approve Enrollment"
                        onclick="updateStatus(<?php echo $student['id']; ?>, 'approve')"
                      >
                        ✓
                      </button>
                      <button 
                        class="action-btn act-reject" 
                        title="Reject Enrollment"
                        onclick="updateStatus(<?php echo $student['id']; ?>, 'reject')"
                      >
                        ✗
                      </button>
                      <button 
                        class="action-btn act-delete" 
                        title="Delete Enrollment permanently"
                        onclick="deleteStudent(<?php echo $student['id']; ?>)"
                      >
                        🗑️
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- ── STUDENT PROFILE DETAIL MODAL ── -->
  <div class="modal-overlay" id="detailModalOverlay" onclick="closeModal()">
    <div class="modal-card" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h4 class="modal-title">Student Registration File</h4>
        <button class="modal-close" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        
        <div class="info-group">
          <span class="info-label">Registration ID</span>
          <span class="info-value" id="m-id"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Application Date</span>
          <span class="info-value" id="m-date"></span>
        </div>

        <div class="info-group">
          <span class="info-label">First Name</span>
          <span class="info-value" id="m-fname"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Middle Name</span>
          <span class="info-value" id="m-mname"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Last Name / Surname</span>
          <span class="info-value" id="m-lname"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Current Review Status</span>
          <span class="info-value" id="m-status"></span>
        </div>

        <div class="info-group">
          <span class="info-label">Active Phone Number</span>
          <span class="info-value" id="m-phone"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Email Address</span>
          <span class="info-value" id="m-email"></span>
        </div>

        <div class="info-group full-width">
          <span class="info-label">Residential Address</span>
          <span class="info-value" id="m-address"></span>
        </div>

        <div class="info-group">
          <span class="info-label">Selected Course</span>
          <span class="info-value" id="m-course" style="color: var(--amber); font-weight: bold;"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Schedule Choice</span>
          <span class="info-value" id="m-schedule"></span>
        </div>

        <div class="info-group">
          <span class="info-label">Identity Documentation</span>
          <span class="info-value" id="m-idtype"></span>
        </div>
        <div class="info-group">
          <span class="info-label">Identification Code</span>
          <span class="info-value" id="m-idnumber"></span>
        </div>

        <!-- Warning panel for underage or no-ID applicants -->
        <div class="info-warning" id="m-chief-warning">
          <strong>⚠️ Identification Notice:</strong> This applicant does not hold a National ID or Birth Certificate. They must provide a passport-sized photograph and an introductory letter from their local sub-location chief or high school principal upon registration.
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal()">Close Window</button>
      </div>
    </div>
  </div>

  <!-- ── PDF EXPORT: FIELD SELECTION MODAL ── -->
  <div class="modal-overlay" id="pdfModalOverlay" onclick="closePdfModal()">
    <div class="modal-card" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h4 class="modal-title">Export Registrations to PDF</h4>
        <button class="modal-close" onclick="closePdfModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p class="pdf-modal-hint">
          Choose which fields to include in the printable PDF. The current search/filter selection on the dashboard will be applied to the export.
        </p>
        <button type="button" class="pdf-select-toggle" onclick="togglePdfFields()" id="pdfToggleBtn">Deselect All</button>

        <div class="pdf-fields-grid" id="pdfFieldsGrid">
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="first_name" checked> First Name</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="middle_name" checked> Middle Name</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="last_name" checked> Last Name</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="id_number" checked> ID / Cert Number</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="phone" checked> Phone</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="email" checked> Email</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="course" checked> Course</label>
          <label class="pdf-field-option"><input type="checkbox" class="pdf-field-cb" value="schedule" checked> Schedule</label>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closePdfModal()">Cancel</button>
        <button class="btn btn-export-pdf" onclick="generatePdf()">Generate PDF</button>
      </div>
    </div>
  </div>

  <!-- ── CERTIFICATE PRINT MODAL ── -->
  <div class="modal-overlay" id="certModalOverlay" onclick="closeCertModal()">
    <div class="modal-card" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h4 class="modal-title">Print Certificates &amp; Transcripts</h4>
        <button class="modal-close" onclick="closeCertModal()">&times;</button>
      </div>
      <div class="modal-body">
        <p class="pdf-modal-hint">
          Each student gets a Certificate of Completion and an Academic Transcript. Choose who to generate documents for.
        </p>

        <div class="cert-mode-choice">
          <div class="cert-mode-card active" id="certModeAllCard" onclick="setCertMode('all')">
            <div class="cert-mode-icon">📜</div>
            <div class="cert-mode-title">All Approved Students</div>
            <div class="cert-mode-sub">Everyone marked "Approved" in the list below</div>
          </div>
          <div class="cert-mode-card" id="certModeSelectCard" onclick="setCertMode('select')">
            <div class="cert-mode-icon">☑️</div>
            <div class="cert-mode-title">Select Specific Students</div>
            <div class="cert-mode-sub">Pick individual names from the list</div>
          </div>
        </div>

        <div class="cert-student-list" id="certStudentList">
          <?php foreach ($enrollments as $student): ?>
            <label class="cert-student-row">
              <input 
                type="checkbox" 
                class="cert-student-cb" 
                value="<?php echo (int)$student['id']; ?>"
                <?php echo strtolower($student['status']) === 'approved' ? 'checked' : ''; ?>
              >
              <div>
                <div class="cert-student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                <div class="cert-student-meta">
                  <?php echo htmlspecialchars($student['course']); ?> &middot; <?php echo htmlspecialchars($student['status']); ?>
                </div>
              </div>
            </label>
          <?php endforeach; ?>
          <?php if (empty($enrollments)): ?>
            <div class="cert-student-row">No students match the current dashboard filters.</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeCertModal()">Cancel</button>
        <button class="btn btn-certificates" onclick="generateCertificates()">Generate Documents</button>
      </div>
    </div>
  </div>

  <!-- ── SECURITY PROFILE MODAL ── -->
  <div class="modal-overlay" id="securityModalOverlay" onclick="closeSecurityModal()">
    <div class="modal-card" style="max-width: 520px;" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h4 class="modal-title">Security Profile Settings</h4>
        <button class="modal-close" onclick="closeSecurityModal()">&times;</button>
      </div>
      <form id="securityProfileForm" style="margin: 0;">
        <div class="modal-body" style="display: flex; flex-direction: column; gap: 1rem; max-height: 70vh; overflow-y: auto;">
          <p class="pdf-modal-hint" style="margin-bottom: 0.5rem;">
            Update your account's email address, security questions/answers, or password. You must verify your current password to save changes.
          </p>

          <div id="sec-error-msg" class="error-msg" style="display: none; background: #FDECEA; border: 1px solid #FFCDD2; border-radius: 8px; padding: 10px 14px; font-size: 0.8rem; color: var(--red); line-height: 1.4;"></div>
          <div id="sec-success-msg" class="success-msg" style="display: none; background: #E8F5E9; border: 1px solid #C8E6C9; border-radius: 8px; padding: 10px 14px; font-size: 0.8rem; color: #2E7D32; line-height: 1.4;"></div>

          <!-- Email -->
          <div class="info-group full-width" style="display: flex; flex-direction: column; gap: 4px;">
            <label class="control-label" for="sec_email">Admin Email Address</label>
            <input type="email" class="input-field" id="sec_email" name="email" value="<?php echo htmlspecialchars($adminProfile['email'] ?? ''); ?>" required>
          </div>

          <!-- Question 1 -->
          <div class="info-group full-width" style="display: flex; flex-direction: column; gap: 4px; border-top: 1px solid var(--glass-border); padding-top: 0.8rem;">
            <label class="control-label" for="sec_q1">Security Question 1</label>
            <input type="text" class="input-field" id="sec_q1" name="q1" value="<?php echo htmlspecialchars($adminProfile['security_question_1'] ?? 'What was the name of your first school?'); ?>" required>
            <input type="password" class="input-field" style="margin-top: 5px;" id="sec_a1" name="a1" placeholder="Enter new answer (leave blank to keep current)">
          </div>

          <!-- Question 2 -->
          <div class="info-group full-width" style="display: flex; flex-direction: column; gap: 4px; border-top: 1px solid var(--glass-border); padding-top: 0.8rem;">
            <label class="control-label" for="sec_q2">Security Question 2</label>
            <input type="text" class="input-field" id="sec_q2" name="q2" value="<?php echo htmlspecialchars($adminProfile['security_question_2'] ?? 'What is your favorite color?'); ?>" required>
            <input type="password" class="input-field" style="margin-top: 5px;" id="sec_a2" name="a2" placeholder="Enter new answer (leave blank to keep current)">
          </div>

          <!-- Question 3 -->
          <div class="info-group full-width" style="display: flex; flex-direction: column; gap: 4px; border-top: 1px solid var(--glass-border); padding-top: 0.8rem;">
            <label class="control-label" for="sec_q3">Security Question 3</label>
            <input type="text" class="input-field" id="sec_q3" name="q3" value="<?php echo htmlspecialchars($adminProfile['security_question_3'] ?? 'In what city or town was your first job?'); ?>" required>
            <input type="password" class="input-field" style="margin-top: 5px;" id="sec_a3" name="a3" placeholder="Enter new answer (leave blank to keep current)">
          </div>

          <!-- New Password -->
          <div class="info-group full-width" style="display: flex; flex-direction: column; gap: 4px; border-top: 1px solid var(--glass-border); padding-top: 0.8rem;">
            <label class="control-label" for="sec_new_password">New Password (Optional)</label>
            <input type="password" class="input-field" id="sec_new_password" name="new_password" placeholder="Enter new password (min 6 characters)">
            <input type="password" class="input-field" style="margin-top: 5px;" id="sec_confirm_password" name="confirm_password" placeholder="Confirm new password">
          </div>

          <!-- Verification Current Password -->
          <div class="info-group full-width" style="display: flex; flex-direction: column; gap: 4px; border-top: 1px solid var(--amber); padding-top: 0.8rem;">
            <label class="control-label" for="sec_current_password" style="color: var(--amber);">Current Password (Required to Save)</label>
            <input type="password" class="input-field" style="border-color: var(--amber);" id="sec_current_password" name="current_password" placeholder="Enter your current password" required>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeSecurityModal()">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveSecurityBtn">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── SYSTEM TOASTS CONTAINER ── -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- ── JAVASCRIPT LOGIC ── -->
  <script>
    // ── Update Student Application Status (Approve/Reject) ──
    function updateStatus(id, action) {
      if (action !== 'approve' && action !== 'reject') return;
      
      const payload = { id: id, action: action };
      
      fetch('update_enrollment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          // Update Badge in table
          const badge = document.getElementById('badge-' + id);
          if (badge) {
            const newStatus = action === 'approve' ? 'Approved' : 'Rejected';
            badge.textContent = newStatus;
            badge.className = 'badge badge-' + newStatus.toLowerCase();
          }
          
          // Re-fetch stat summary values from server to keep counts active
          updateStatsSummary();
          
          showToast(data.message, 'success');
        } else {
          showToast(data.message, 'error');
        }
      })
      .catch(err => {
        showToast('Error updating enrollment status.', 'error');
        console.error(err);
      });
    }

    // ── Delete a Student Application ──
    function deleteStudent(id) {
      if (!confirm("Are you sure you want to permanently delete this registration record? This action cannot be undone.")) {
        return;
      }
      
      const payload = { id: id, action: 'delete' };
      
      fetch('update_enrollment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          // Delete row from table with a nice fadeout animation
          const row = document.getElementById('row-' + id);
          if (row) {
            row.style.transition = 'opacity 0.4s ease';
            row.style.opacity = '0';
            setTimeout(() => {
              row.remove();
              // Check if table is empty
              const tableBody = document.querySelector('#enrollments-table tbody');
              if (tableBody && tableBody.children.length === 0) {
                tableBody.innerHTML = `
                  <tr>
                    <td colspan="8">
                      <div class="empty-state">
                        <div class="empty-state-icon">📂</div>
                        <p>No enrollment applications found matching the filter criteria.</p>
                      </div>
                    </td>
                  </tr>
                `;
              }
              // Update count indicator
              const countTag = document.getElementById('table-count');
              if (countTag) {
                const currentCount = parseInt(countTag.textContent);
                if (!isNaN(currentCount)) {
                  countTag.textContent = (currentCount - 1) + " matches";
                }
              }
            }, 400);
          }
          
          // Re-fetch stats count
          updateStatsSummary();
          
          showToast(data.message, 'success');
        } else {
          showToast(data.message, 'error');
        }
      })
      .catch(err => {
        showToast('Error deleting registration record.', 'error');
        console.error(err);
      });
    }

    // ── Fetch statistics from backend asynchronously to update counts without reloading ──
    function updateStatsSummary() {
      // We will perform a silent fetch requests to parse counts
      fetch('page_admin_dashboard.php')
      .then(res => res.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Sync counts
        ['total', 'pending', 'approved', 'rejected'].forEach(stat => {
          const valElement = doc.getElementById('stat-' + stat);
          const targetElement = document.getElementById('stat-' + stat);
          if (valElement && targetElement) {
            targetElement.textContent = valElement.textContent;
          }
        });
      });
    }

    // ── View Student Detail Dialog Modal ──
    function viewStudent(student) {
      document.getElementById('m-id').textContent = student.id;
      document.getElementById('m-date').textContent = student.created_at;
      document.getElementById('m-fname').textContent = student.first_name;
      document.getElementById('m-mname').textContent = student.middle_name ? student.middle_name : '—';
      document.getElementById('m-lname').textContent = student.last_name;
      document.getElementById('m-status').textContent = student.status;
      document.getElementById('m-phone').textContent = student.phone;
      document.getElementById('m-email').textContent = student.email ? student.email : '—';
      document.getElementById('m-address').textContent = student.address;
      document.getElementById('m-course').textContent = student.course;
      document.getElementById('m-schedule').textContent = student.schedule ? student.schedule : '—';
      
      const hasId = parseInt(student.has_id);
      const warningBlock = document.getElementById('m-chief-warning');
      
      if (hasId === 1) {
        document.getElementById('m-idtype').textContent = student.id_type;
        document.getElementById('m-idnumber').textContent = student.id_number;
        warningBlock.style.display = 'none';
      } else {
        document.getElementById('m-idtype').textContent = 'None Provided';
        document.getElementById('m-idnumber').textContent = '—';
        warningBlock.style.display = 'block';
      }

      const modal = document.getElementById('detailModalOverlay');
      modal.classList.add('active');
    }

    function closeModal() {
      document.getElementById('detailModalOverlay').classList.remove('active');
    }

    // ── PDF Export: field-selection modal ──
    function openPdfModal() {
      document.getElementById('pdfModalOverlay').classList.add('active');
    }

    function closePdfModal() {
      document.getElementById('pdfModalOverlay').classList.remove('active');
    }

    function togglePdfFields() {
      const boxes = document.querySelectorAll('.pdf-field-cb');
      const toggleBtn = document.getElementById('pdfToggleBtn');
      const shouldSelectAll = toggleBtn.textContent.trim() === 'Select All';
      boxes.forEach(cb => cb.checked = shouldSelectAll);
      toggleBtn.textContent = shouldSelectAll ? 'Deselect All' : 'Select All';
    }

    function generatePdf() {
      const checked = Array.from(document.querySelectorAll('.pdf-field-cb:checked')).map(cb => cb.value);

      if (checked.length === 0) {
        showToast('Please select at least one field to include in the PDF.', 'error');
        return;
      }

      // Carry over the current dashboard filters so the PDF matches the visible list
      const params = new URLSearchParams();
      params.set('search', <?php echo json_encode($searchFilter); ?>);
      params.set('schedule', <?php echo json_encode($scheduleFilter); ?>);
      checked.forEach(field => params.append('fields[]', field));

      window.location.href = 'export_pdf.php?' + params.toString();
      closePdfModal();
    }

    // ── Certificate & Transcript printing ──
    let certMode = 'all';

    function openCertModal() {
      document.getElementById('certModalOverlay').classList.add('active');
    }

    function closeCertModal() {
      document.getElementById('certModalOverlay').classList.remove('active');
    }

    function setCertMode(mode) {
      certMode = mode;
      document.getElementById('certModeAllCard').classList.toggle('active', mode === 'all');
      document.getElementById('certModeSelectCard').classList.toggle('active', mode === 'select');
      document.getElementById('certStudentList').classList.toggle('visible', mode === 'select');
    }

    function generateCertificates() {
      const params = new URLSearchParams();

      if (certMode === 'all') {
        params.set('mode', 'all');
      } else {
        const selectedIds = Array.from(document.querySelectorAll('.cert-student-cb:checked')).map(cb => cb.value);
        if (selectedIds.length === 0) {
          showToast('Please select at least one student.', 'error');
          return;
        }
        params.set('mode', 'select');
        selectedIds.forEach(id => params.append('ids[]', id));
      }

      window.location.href = 'print_certificates.php?' + params.toString();
      closeCertModal();
    }

    // ── Toast notification system ──
    function showToast(message, type = 'success') {
      const container = document.getElementById('toastContainer');
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      
      const icon = type === 'success' ? '✅' : '❌';
      toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
      
      container.appendChild(toast);
      
      // Auto fade-out and removal
      setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => {
          toast.remove();
        }, 300);
      }, 4000);
    }

    // ── Security Profile Modal functions ──
    function openSecurityModal() {
      document.getElementById('securityModalOverlay').classList.add('active');
      document.getElementById('sec-error-msg').style.display = 'none';
      document.getElementById('sec-success-msg').style.display = 'none';
      document.getElementById('sec_current_password').value = '';
      document.getElementById('sec_new_password').value = '';
      document.getElementById('sec_confirm_password').value = '';
    }

    function closeSecurityModal() {
      document.getElementById('securityModalOverlay').classList.remove('active');
    }

    document.getElementById('securityProfileForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const email = document.getElementById('sec_email').value.trim();
      const currentPassword = document.getElementById('sec_current_password').value;
      const newPassword = document.getElementById('sec_new_password').value;
      const confirmPassword = document.getElementById('sec_confirm_password').value;

      const errorMsg = document.getElementById('sec-error-msg');
      const successMsg = document.getElementById('sec-success-msg');

      errorMsg.style.display = 'none';
      successMsg.style.display = 'none';

      if (newPassword && newPassword !== confirmPassword) {
        errorMsg.textContent = 'New passwords do not match.';
        errorMsg.style.display = 'block';
        return;
      }

      if (newPassword && newPassword.length < 6) {
        errorMsg.textContent = 'New password must be at least 6 characters.';
        errorMsg.style.display = 'block';
        return;
      }

      const saveBtn = document.getElementById('saveSecurityBtn');
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      const formData = new FormData(this);

      fetch('api_update-profile.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';

        if (data.status === 'success') {
          successMsg.textContent = data.message;
          successMsg.style.display = 'block';
          showToast(data.message, 'success');
          
          // Clear password fields
          document.getElementById('sec_current_password').value = '';
          document.getElementById('sec_new_password').value = '';
          document.getElementById('sec_confirm_password').value = '';
          
          setTimeout(() => {
            closeSecurityModal();
          }, 2000);
        } else {
          errorMsg.textContent = data.message;
          errorMsg.style.display = 'block';
          showToast(data.message, 'error');
        }
      })
      .catch(err => {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
        errorMsg.textContent = 'An unexpected error occurred. Please try again.';
        errorMsg.style.display = 'block';
        console.error(err);
      });
    });
  </script>
</body>
</html>
