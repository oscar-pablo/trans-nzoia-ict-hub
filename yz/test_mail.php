<?php
/**
 * test_mail.php
 * Run this ONCE in your browser to test your email connection.
 * DELETE this file after testing — do not leave it on production.
 *
 * Access at: http://localhost/transnzoia-ict-hub/yz/test_mail.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mail_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── CONFIG: change this to YOUR real email to receive the test ───────────────
$sendTestTo      = 'musunguoscar7@gmail.com'; // ← put your email here
$sendTestToName  = 'Test User';
// ─────────────────────────────────────────────────────────────────────────────

echo '<!DOCTYPE html><html><head>
<meta charset="UTF-8">
<title>Mail Test — Trans-Nzoia ICT Hub</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 20px; background: #f4f6f9; }
  h2   { color: #0a192f; }
  .box { background: #fff; border-radius: 10px; padding: 24px 28px; margin-top: 20px; border: 1px solid #e2e8f0; }
  .ok  { color: #16a34a; font-weight: bold; font-size: 18px; }
  .err { color: #dc2626; font-weight: bold; font-size: 18px; }
  .info{ background:#f0f9ff; border-left:4px solid #0a192f; padding:12px 16px; border-radius:0 8px 8px 0; margin:14px 0; font-size:14px; }
  pre  { background:#1e293b; color:#7dd3fc; padding:16px; border-radius:8px; font-size:13px; overflow-x:auto; }
</style>
</head><body>';

echo '<h2>📧 Trans-Nzoia ICT Hub — Mail Connection Test</h2>';
echo '<div class="box">';

// Show current config
echo '<div class="info">';
echo '<strong>Environment:</strong> ' . APP_ENV . '<br>';
echo '<strong>SMTP Host:</strong> '   . MAIL_HOST . '<br>';
echo '<strong>SMTP Port:</strong> '   . MAIL_PORT . '<br>';
echo '<strong>Username:</strong> '    . MAIL_USERNAME . '<br>';
echo '<strong>Sending to:</strong> '  . $sendTestTo;
echo '</div>';

$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo '<pre>' . htmlspecialchars($str) . '</pre>';
    };

    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;

    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($sendTestTo, $sendTestToName);

    $mail->isHTML(true);
    $mail->Subject = '✅ Test Email — Trans-Nzoia ICT Hub';
    $mail->Body    = '
        <div style="font-family:Arial,sans-serif;padding:24px;background:#f4f6f9;">
            <div style="background:#0a192f;padding:20px;border-radius:10px 10px 0 0;text-align:center;">
                <h2 style="color:#fff;margin:0;">Trans-Nzoia Community ICT Hub</h2>
            </div>
            <div style="background:#fff;padding:24px;border-radius:0 0 10px 10px;">
                <p style="color:#16a34a;font-size:18px;font-weight:bold;">✅ Connection Successful!</p>
                <p style="color:#475569;">Your email setup is working correctly. Enrollment confirmation emails will be sent successfully.</p>
                <p style="color:#94a3b8;font-size:13px;">Environment: <strong>' . APP_ENV . '</strong> · Host: <strong>' . MAIL_HOST . '</strong></p>
            </div>
        </div>';
    $mail->AltBody = 'Connection test successful! Your email setup is working.';

    $mail->send();

    echo '<p class="ok">✅ SUCCESS! Email sent successfully.</p>';
    echo '<p style="color:#475569;">Check your ' . (APP_ENV === 'local' ? '<strong>Mailtrap Sandbox inbox</strong>' : '<strong>email inbox at ' . $sendTestTo . '</strong>') . ' for the test email.</p>';

} catch (Exception $e) {
    echo '<p class="err">❌ FAILED — Email could not be sent.</p>';
    echo '<p style="color:#dc2626;"><strong>Error:</strong> ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
    echo '<p style="color:#64748b;font-size:14px;">Check the debug output above for details.</p>';
}

echo '</div>';
echo '<p style="color:#94a3b8;font-size:12px;margin-top:20px;">⚠️ Delete this file after testing. Do not leave it on a live server.</p>';
echo '</body></html>';
?>
