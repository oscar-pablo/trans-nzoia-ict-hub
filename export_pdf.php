<?php
/**
 * export_pdf.php
 * Generates a printable PDF of enrollment registrations for the admin dashboard.
 * The admin chooses which fields to include (via the modal on page_admin_dashboard.php)
 * and the current search/filter selection is carried over so the PDF matches
 * what is shown on screen.
 *
 * Requires the Dompdf library:
 *   composer require dompdf/dompdf
 *
 * If you are not using Composer anywhere else on this project, run that command
 * once in the project root - it creates a vendor/ folder with everything Dompdf needs.
 */
session_start();

// ── Security: same guard as the dashboard ──────────────────────────────────
if (!isset($_SESSION['admin_id']) || $_SESSION['logged_in'] !== true) {
    header("Location: page_admin-login.php");
    exit;
}

require_once 'page_db.php';
require_once __DIR__ . '/yz/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Whitelist of exportable fields (label => column) ───────────────────────
// Keeping this as a whitelist (rather than trusting $_GET directly) stops anyone
// from injecting arbitrary column names into the SQL SELECT list.
$fieldCatalog = [
    'first_name'   => 'First Name',
    'middle_name'  => 'Middle Name',
    'last_name'    => 'Last Name',
    'id_number'    => 'ID / Cert Number',
    'phone'        => 'Phone',
    'email'        => 'Email',
    'course'       => 'Course',
    'schedule'     => 'Schedule',
];

// ── Read + sanitize the requested fields ───────────────────────────────────
$requestedFields = $_GET['fields'] ?? [];
if (!is_array($requestedFields)) {
    $requestedFields = [];
}

// Keep only known columns, and keep them in the catalog's canonical order
$selectedFields = array_keys($fieldCatalog);
$selectedFields = array_values(array_intersect($selectedFields, $requestedFields));

if (empty($selectedFields)) {
    die("No valid fields were selected for the PDF export. Please go back and select at least one field.");
}

// ── Re-apply the same filters used on the dashboard ────────────────────────
$search   = trim($_GET['search'] ?? '');
$schedule = trim($_GET['schedule'] ?? '');

$sql = "SELECT " . implode(', ', $selectedFields) . " FROM enrollments WHERE 1=1";
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
} catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
}

// ── Watermark image, inlined as base64 so it prints on every page ──────────
$logoPath = __DIR__ . '/images/logo.png';
$logoDataUri = '';
if (file_exists($logoPath)) {
    $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// ── Helper to format a cell's value for display ────────────────────────────
function formatCell($field, $value) {
    if ($field === 'has_id') {
        return $value ? 'Yes' : 'No';
    }
    if ($value === null || $value === '') {
        return '&mdash;';
    }
    return htmlspecialchars($value);
}

$generatedOn = date('d M Y, H:i');
$activeFiltersSummary = [];
if ($search !== '')   $activeFiltersSummary[] = "Search: \"$search\"";
if ($schedule !== '') $activeFiltersSummary[] = "Schedule: $schedule";
$filtersLine = empty($activeFiltersSummary) ? 'All records (no filters applied)' : implode(' | ', $activeFiltersSummary);

// ── Build the printable HTML ────────────────────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: A4 landscape;
        margin: 90px 30px 60px 30px;
    }

    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #1A1A1A;
        font-size: 11px;
    }

    /* Watermark: centered, translucent, sits behind the table on every page */
    .watermark {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 260px;
        height: 260px;
        opacity: 0.08;
        z-index: -1;
    }

    .doc-header {
        position: fixed;
        top: -70px;
        left: 0;
        right: 0;
        height: 60px;
        display: table;
        width: 100%;
        border-bottom: 2px solid #003049;
        padding-bottom: 8px;
    }

    .doc-header .h-left {
        display: table-cell;
        vertical-align: middle;
        width: 60px;
    }

    .doc-header .h-left img {
        width: 46px;
        height: 46px;
    }

    .doc-header .h-right {
        display: table-cell;
        vertical-align: middle;
        padding-left: 12px;
    }

    .doc-header h1 {
        font-size: 16px;
        color: #003049;
        margin: 0 0 2px 0;
    }

    .doc-header .subtitle {
        font-size: 10px;
        color: #5C5040;
    }

    .doc-footer {
        position: fixed;
        bottom: -50px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 8.5px;
        color: #8b9ba8;
        border-top: 1px solid #ccc;
        padding-top: 6px;
    }

    .meta-line {
        font-size: 10px;
        color: #5C5040;
        margin-bottom: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #003049;
        color: #ffffff;
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 6px 8px;
        text-align: left;
    }

    td {
        padding: 5px 8px;
        border-bottom: 1px solid #e2e2e2;
        font-size: 10px;
    }

    tr:nth-child(even) td {
        background: #f7f7f7;
    }
</style>
</head>
<body>

    <?php if ($logoDataUri): ?>
        <img src="<?php echo $logoDataUri; ?>" class="watermark">
    <?php endif; ?>

    <div class="doc-header">
        <div class="h-left">
            <?php if ($logoDataUri): ?>
                <img src="<?php echo $logoDataUri; ?>">
            <?php endif; ?>
        </div>
        <div class="h-right">
            <h1>Trans-Nzoia Community ICT Hub</h1>
            <div class="subtitle">Enrollment Registrations Report</div>
        </div>
    </div>

    <div class="doc-footer">
        Trans-Nzoia Community ICT Hub &mdash; Confidential Administrative Document &mdash; Generated <?php echo $generatedOn; ?>
    </div>

    <div class="meta-line">
        <strong>Filters applied:</strong> <?php echo htmlspecialchars($filtersLine); ?> &nbsp;|&nbsp;
        <strong>Total records:</strong> <?php echo count($records); ?> &nbsp;|&nbsp;
        <strong>Generated on:</strong> <?php echo $generatedOn; ?>
    </div>

    <table>
        <thead>
            <tr>
                <?php foreach ($selectedFields as $field): ?>
                    <th><?php echo htmlspecialchars($fieldCatalog[$field]); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="<?php echo count($selectedFields); ?>" style="text-align:center; padding: 20px;">
                        No records match the applied filters.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($records as $row): ?>
                    <tr>
                        <?php foreach ($selectedFields as $field): ?>
                            <td><?php echo formatCell($field, $row[$field] ?? ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
<?php
$html = ob_get_clean();

// ── Render with Dompdf ──────────────────────────────────────────────────────
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'TransNzoia_ICT_Hub_Enrollments_' . date('Y-m-d_His') . '.pdf';

// 'I' streams inline (opens/prints in browser); use 'D' to force a download instead
$dompdf->stream($filename, ['Attachment' => false]);
exit;