<?php
/**
 * admin_dashboard.php
 * Secure Administrator Dashboard.
 * Allows searching, filtering, status updates (Approve/Reject), and deletion of registrations.
 * Supports CSV export.
 */
session_start();

// Security: Redirect unauthorized users back to login
if (!isset($_SESSION['admin_id']) || $_SESSION['logged_in'] !== true) {
    header("Location: admin-login.php");
    exit;
}

require_once 'db.php';

// ── CSV EXPORT ──────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Retrieve current filters to export the filtered set
    $search   = trim($_GET['search'] ?? '');
    $course   = trim($_GET['course'] ?? '');
    $status   = trim($_GET['status'] ?? '');
    $schedule = trim($_GET['schedule'] ?? '');

    $sql = "SELECT id, first_name, middle_name, last_name, has_id, id_type, id_number, phone, email, address, course, schedule, status, created_at FROM enrollments WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR phone LIKE :search OR id_number LIKE :search OR email LIKE :search OR address LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if ($course !== '') {
        $sql .= " AND course = :course";
        $params[':course'] = $course;
    }
    if ($status !== '') {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
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

} catch (PDOException $e) {
    die("Database stats load failed: " . $e->getMessage());
}

// ── DYNAMIC FILTERING & SEARCH ────────────────────────────────────────────
$searchFilter   = trim($_GET['search'] ?? '');
$courseFilter   = trim($_GET['course'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$scheduleFilter = trim($_GET['schedule'] ?? '');

$sql = "SELECT * FROM enrollments WHERE 1=1";
$params = [];

if ($searchFilter !== '') {
    $sql .= " AND (first_name LIKE :search OR middle_name LIKE :search OR last_name LIKE :search OR phone LIKE :search OR id_number LIKE :search OR email LIKE :search OR address LIKE :search)";
    $params[':search'] = "%$searchFilter%";
}
if ($courseFilter !== '') {
    $sql .= " AND course = :course";
    $params[':course'] = $courseFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
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
      width: 40px; height: 40px;
      background: var(--red);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Poppins', sans-serif;
      font-weight: 800; color: var(--white);
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
      <div class="logo-badge">TN</div>
      <div class="logo-title">
        Trans-Nzoia Community ICT Hub
        <small>Administration Dashboard</small>
      </div>
    </div>
    <div class="admin-profile">
      <div class="admin-name">Welcome, <span><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span></div>
      <a href="logout.php" class="logout-btn">Log Out</a>
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
      <form method="GET" action="admin_dashboard.php" class="filter-form">
        
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
          <label class="control-label" for="course">Course Filter</label>
          <select class="input-field" id="course" name="course">
            <option value="">All Courses</option>
            <option value="Computer Packages (Word, Excel, PowerPoint)" <?php echo $courseFilter === 'Computer Packages (Word, Excel, PowerPoint)' ? 'selected' : ''; ?>>Computer Packages</option>
            <option value="Web Development Bootcamp" <?php echo $courseFilter === 'Web Development Bootcamp' ? 'selected' : ''; ?>>Web Dev Bootcamp</option>
            <option value="Python and Data Science" <?php echo $courseFilter === 'Python and Data Science' ? 'selected' : ''; ?>>Python & Data Science</option>
            <option value="Cybersecurity Basics" <?php echo $courseFilter === 'Cybersecurity Basics' ? 'selected' : ''; ?>>Cybersecurity Basics</option>
            <option value="Graphic Design" <?php echo $courseFilter === 'Graphic Design' ? 'selected' : ''; ?>>Graphic Design</option>
            <option value="Digital Marketing" <?php echo $courseFilter === 'Digital Marketing' ? 'selected' : ''; ?>>Digital Marketing</option>
          </select>
        </div>

        <div class="control-group">
          <label class="control-label" for="status">Status Filter</label>
          <select class="input-field" id="status" name="status">
            <option value="">All Statuses</option>
            <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
            <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
          </select>
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
          <a href="admin_dashboard.php" class="btn btn-secondary">Reset</a>
          <a 
            href="admin_dashboard.php?export=csv&search=<?php echo urlencode($searchFilter); ?>&course=<?php echo urlencode($courseFilter); ?>&status=<?php echo urlencode($statusFilter); ?>&schedule=<?php echo urlencode($scheduleFilter); ?>" 
            class="btn btn-export" 
            title="Download records as spreadsheet CSV"
          >
            Export CSV
          </a>
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
      fetch('admin_dashboard.php')
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
  </script>
</body>
</html>
