<?php
/**
 * print_certificates.php
 * Generates a Certificate of Completion + Academic Transcript (2 pages per student)
 * for either every "Approved" student, or a hand-picked list of students chosen
 * from the "Print Certificates" modal on the admin dashboard.
 *
 * Requires the Dompdf library (same one used by export_pdf.php):
 *   composer require dompdf/dompdf
 *
 * ── Things you may want to customize ───────────────────────────────────────
 * 1. $courseCurriculum below only has the subject/area-of-study table filled in
 *    for "Computer Packages (Word, Excel, PowerPoint)" (matching your sample).
 *    Add the subject lists for your other courses the same way.
 * 2. There's no separate "completion date" column in the enrollments table yet,
 *    so both documents currently use the registration date (created_at). If you
 *    add a completed_at / approved_at column later, swap it in below.
 * 3. The partner logo strip (Allan Chesang Foundation, AJIRA, JITUME, KONZA) is
 *    rendered as text for now since I don't have those image files. Drop them
 *    into images/ and I can swap the text badges for real logos.
 * 4. The gold seal is a CSS approximation, not the embossed foil sticker from
 *    your printed sample — happy to refine this further if you'd like.
 */
session_start();

// ── Security: same guard as the rest of the admin area ─────────────────────
if (!isset($_SESSION['admin_id']) || $_SESSION['logged_in'] !== true) {
    header("Location: page_admin-login.php");
    exit;
}

require_once 'page_db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Work out which students to generate documents for ──────────────────────
$mode = $_GET['mode'] ?? 'all';

if ($mode === 'select') {
    $rawIds = $_GET['ids'] ?? [];
    // Keep only clean integers so nothing odd can reach the SQL
    $ids = array_values(array_filter(array_map('intval', (array)$rawIds), fn($v) => $v > 0));

    if (empty($ids)) {
        die("No students were selected. Please go back and choose at least one student.");
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT * FROM enrollments WHERE id IN ($placeholders) ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
} else {
    // "All" = everyone who has actually finished, i.e. status = Approved
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE status = 'Approved' ORDER BY id ASC");
    $stmt->execute();
}

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($students)) {
    die("No matching students were found to generate certificates for.");
}

// ── Course curricula: subject / area-of-study table shown on the transcript ─
// Add entries here for each course offered at the hub.
$courseCurriculum = [
    'Computer Packages (Word, Excel, PowerPoint)' => [
        ['Introduction',        'Introduction to computers'],
        ['Desktop Publication', 'Windows'],
        ['Desktop Publication', 'Ms-Word'],
        ['Keyboarding',         'Typing'],
        ['Spreadsheets',        'Ms-Excel'],
        ['Database Management','Ms-Access'],
        ['Presentation',        'Power Point'],
        ['Desktop Publication', 'Ms-Publisher'],
        ['Outlook Presentation','Internet & E-mail'],
    ],
    'Web Development Bootcamp' => [],
    'Python and Data Science'  => [],
    'Cybersecurity Basics'     => [],
    'Graphic Design'           => [],
    'Digital Marketing'        => [],
];

// ── Watermark / header badge, inlined as base64 ─────────────────────────────
$logoPath = __DIR__ . '/images/logo.png';
$logoDataUri = '';
if (file_exists($logoPath)) {
    $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}

// ── Small helpers ───────────────────────────────────────────────────────────
function ordinalSuffix($day) {
    $day = (int)$day;
    if ($day % 10 === 1 && $day !== 11) return 'ST';
    if ($day % 10 === 2 && $day !== 12) return 'ND';
    if ($day % 10 === 3 && $day !== 13) return 'RD';
    return 'TH';
}

function certificateId($student) {
    $date = strtotime($student['created_at']);
    return sprintf('TNCIH/%04d/%02d/%02d', $student['id'], date('m', $date), date('y', $date));
}

// ── Build the printable HTML: 2 pages per student ───────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: A4 portrait;
        margin: 30px 35px;
    }

    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #1A1A1A;
    }

    .doc-page {
        position: relative;
        border: 3px solid #003049;
        padding: 30px 35px;
        min-height: 720px;
    }

    .doc-page.break-after {
        page-break-after: always;
    }

    .doc-id {
        font-size: 9px;
        color: #5C5040;
    }

    .doc-header {
        text-align: center;
        margin-top: 6px;
        margin-bottom: 18px;
    }

    .doc-header img {
        width: 60px;
        height: 60px;
        margin-bottom: 6px;
    }

    .doc-header h1 {
        font-size: 20px;
        color: #003049;
        margin: 0;
        letter-spacing: 0.5px;
    }

    .doc-header .transcript-tag {
        color: #D62828;
        font-size: 13px;
        font-weight: bold;
        text-decoration: underline;
        margin-top: 6px;
    }

    /* ── Certificate-specific styling ── */
    .cert-awards-line {
        text-align: center;
        font-size: 12px;
        margin-top: 10px;
    }

    .cert-title {
        text-align: center;
        font-family: 'Georgia', serif;
        font-style: italic;
        font-size: 40px;
        color: #D62828;
        margin: 6px 0;
    }

    .cert-to {
        text-align: center;
        font-size: 12px;
        margin-bottom: 10px;
    }

    .cert-name {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        color: #0057b7;
        margin-bottom: 14px;
    }

    .cert-body-line {
        text-align: center;
        font-size: 12px;
        margin-bottom: 8px;
    }

    .cert-course {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        color: #D62828;
        margin-bottom: 24px;
    }

    .cert-date-line {
        text-align: center;
        font-size: 12px;
        margin-bottom: 50px;
    }

    .cert-date-line strong {
        border-bottom: 1px dotted #333;
        padding: 0 4px;
    }

    .signature-row {
        display: table;
        width: 100%;
        margin-bottom: 20px;
    }

    .signature-cell {
        display: table-cell;
        width: 50%;
        text-align: center;
        font-size: 11px;
        color: #333;
    }

    .signature-line {
        border-top: 1px solid #333;
        width: 70%;
        margin: 30px auto 6px;
    }

    .official-seal {
        position: absolute;
        bottom: 90px;
        right: 50px;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 30%, #fff6cf, #d4a017 60%, #8a6d00 100%);
        border: 3px double #6b5000;
        box-shadow: 0 2px 5px rgba(0,0,0,0.35);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: #4a3900;
        transform: rotate(-8deg);
    }

    .official-seal .seal-top {
        font-size: 7px;
        font-weight: bold;
        letter-spacing: 0.5px;
    }

    .official-seal .seal-mid {
        font-size: 13px;
        font-weight: bold;
        margin: 2px 0;
    }

    .official-seal .seal-bottom {
        font-size: 6px;
    }

    .doc-disclaimer {
        text-align: center;
        font-size: 9px;
        font-style: italic;
        color: #D62828;
        margin-bottom: 14px;
    }

    .partner-strip {
        display: table;
        width: 100%;
        border-top: 1px solid #ddd;
        padding-top: 8px;
    }

    .partner-strip .partner-badge {
        display: table-cell;
        text-align: center;
        font-size: 8.5px;
        font-weight: bold;
        color: #555;
    }

    /* ── Transcript-specific styling ── */
    .transcript-name {
        text-align: center;
        font-size: 15px;
        font-weight: bold;
        color: #003049;
        text-decoration: underline;
        margin: 10px 0;
    }

    .transcript-meta-row {
        display: table;
        width: 100%;
        font-size: 11px;
        margin-bottom: 10px;
    }

    .transcript-meta-row .meta-cell {
        display: table-cell;
        width: 50%;
    }

    .transcript-meta-row .meta-label {
        color: #D62828;
        font-weight: bold;
    }

    .transcript-summary {
        text-align: center;
        font-size: 11px;
        margin-bottom: 16px;
    }

    .subjects-caption {
        text-align: center;
        font-size: 11px;
        font-weight: bold;
        margin-bottom: 8px;
    }

    table.subjects-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    table.subjects-table th {
        background: #003049;
        color: #fff;
        font-size: 10px;
        padding: 6px 8px;
        text-align: left;
    }

    table.subjects-table td {
        border: 1px solid #ccc;
        padding: 6px 8px;
        font-size: 10px;
    }
</style>
</head>
<body>

<?php
$totalStudents = count($students);
foreach ($students as $index => $student):
    $isLastStudent = ($index === $totalStudents - 1);
    $fullName = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
    $certId = certificateId($student);
    $date = strtotime($student['created_at']);
    $day = date('j', $date);
    $monthName = strtoupper(date('F', $date));
    $year = date('Y', $date);
    $curriculum = $courseCurriculum[$student['course']] ?? [];
?>

    <!-- ══ PAGE 1: CERTIFICATE OF COMPLETION ══ -->
    <div class="doc-page break-after">
        <div class="doc-id"><?php echo htmlspecialchars($certId); ?></div>

        <div class="doc-header">
            <?php if ($logoDataUri): ?><img src="<?php echo $logoDataUri; ?>"><?php endif; ?>
            <h1>TRANS NZOIA COMMUNITY<br>ICT HUB</h1>
        </div>

        <div class="cert-awards-line">awards this</div>
        <div class="cert-title">Certificate</div>
        <div class="cert-to">to</div>
        <div class="cert-name"><?php echo htmlspecialchars(strtoupper($fullName)); ?></div>
        <div class="cert-body-line">for having completed the studies, examined and passed the</div>
        <div class="cert-course"><?php echo htmlspecialchars(strtoupper($student['course'])); ?></div>

        <div class="cert-date-line">
            On this <strong><?php echo $day . ordinalSuffix($day); ?></strong> Day of
            <strong><?php echo $monthName; ?></strong> <strong><?php echo $year; ?></strong>
        </div>

        <div class="signature-row">
            <div class="signature-cell">
                <div class="signature-line"></div>
                Instructor
            </div>
            <div class="signature-cell">
                <div class="signature-line"></div>
                Director
            </div>
        </div>

        <div class="official-seal">
            <div class="seal-top">TRANS NZOIA</div>
            <div class="seal-mid">TNCIH</div>
            <div class="seal-bottom">COMMUNITY ICT HUB</div>
        </div>

        <div class="doc-disclaimer">Certificate was issued without any erasure or alteration whatsoever.</div>

        <div class="partner-strip">
            <div class="partner-badge">Allan Chesang<br>Foundation</div>
            <div class="partner-badge">AJIRA</div>
            <div class="partner-badge">JITUME</div>
            <div class="partner-badge">KONZA</div>
        </div>
    </div>

    <!-- ══ PAGE 2: ACADEMIC TRANSCRIPT ══ -->
    <div class="doc-page<?php echo $isLastStudent ? '' : ' break-after'; ?>">
        <div class="doc-header">
            <?php if ($logoDataUri): ?><img src="<?php echo $logoDataUri; ?>"><?php endif; ?>
            <h1>TRANS NZOIA COMMUNITY ICT HUB</h1>
            <div class="transcript-tag">ACADEMIC TRANSCRIPT:</div>
        </div>

        <div class="cert-to" style="margin-bottom:0;">This is to certify that:</div>
        <div class="transcript-name"><?php echo htmlspecialchars(strtoupper($fullName)); ?></div>

        <div class="transcript-meta-row">
            <div class="meta-cell"><span class="meta-label">Admin No:</span></div>
            <div class="meta-cell" style="text-align:right;"><?php echo htmlspecialchars($certId); ?></div>
        </div>

        <div class="transcript-summary">
            The above named student did their Certificate course in
            <strong><?php echo htmlspecialchars($student['course']); ?></strong> and satisfactorily completed.
        </div>

        <div class="subjects-caption">The following were the subject areas covered:</div>

        <table class="subjects-table">
            <thead>
                <tr>
                    <th style="width:50%;">Subject</th>
                    <th style="width:50%;">Area of Study</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($curriculum)): ?>
                    <tr>
                        <td colspan="2" style="text-align:center; color:#999;">
                            Curriculum details not yet configured for this course.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($curriculum as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row[0]); ?></td>
                            <td><?php echo htmlspecialchars($row[1]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signature-row">
            <div class="signature-cell">
                <div class="signature-line"></div>
                Instructor<br>
                Date: <?php echo date('d/m/Y', $date); ?>
            </div>
            <div class="signature-cell">
                <div class="signature-line"></div>
                Director
            </div>
        </div>

        <div class="doc-disclaimer">This transcript was issued without any erasure or alteration whatsoever.</div>

        <div class="partner-strip">
            <div class="partner-badge">Allan Chesang<br>Foundation</div>
            <div class="partner-badge">AJIRA</div>
            <div class="partner-badge">JITUME</div>
            <div class="partner-badge">KONZA</div>
        </div>
    </div>

<?php endforeach; ?>

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
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'TNCIH_Certificates_' . date('Y-m-d_His') . '.pdf';

// Streams inline so the admin can preview and print directly from the browser
$dompdf->stream($filename, ['Attachment' => false]);
exit;
