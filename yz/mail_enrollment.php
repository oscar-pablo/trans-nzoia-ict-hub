<?php
/**
 * mail_enrollment.php
 * Sends a confirmation email to a student after successful enrollment submission.
 *
 * Usage:
 *   require_once 'mail_enrollment.php';
 *   $result = sendEnrollmentConfirmation($firstName, $email, $course);
 *   // $result = ['sent' => true] or ['sent' => false, 'error' => '...']
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mail_config.php';

function sendEnrollmentConfirmation(string $firstName, string $studentEmail, string $course): array
{
    // If no email provided, silently skip — enrollment still succeeds
    if (empty(trim($studentEmail))) {
        return ['sent' => false, 'error' => 'No email address provided.'];
    }

    $mail = new PHPMailer(true);

    try {
        // ── Server settings ──────────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // ── Sender & recipient ───────────────────────────────────────────────
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($studentEmail, $firstName);
        $mail->addReplyTo('info@transnzoiaict.co.ke', 'Trans-Nzoia ICT Hub');

        // ── Content ──────────────────────────────────────────────────────────
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Your Enrollment Request Has Been Received — Trans-Nzoia ICT Hub';
        $mail->Body    = buildEmailHTML($firstName, $course);
        $mail->AltBody = buildEmailPlainText($firstName, $course); // fallback for plain-text clients

        $mail->send();
        return ['sent' => true];

    } catch (Exception $e) {
        return ['sent' => false, 'error' => $mail->ErrorInfo];
    }
}

// ─── HTML Email Template ──────────────────────────────────────────────────────
function buildEmailHTML(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $year      = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enrollment Confirmation</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:32px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          <!-- Header -->
          <tr>
            <td style="background:#0a192f;border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
              <p style="margin:0 0 4px;font-size:13px;color:rgba(255,255,255,0.55);letter-spacing:0.08em;text-transform:uppercase;">National Government Initiative</p>
              <h1 style="margin:0;font-size:22px;font-weight:900;color:#ffffff;line-height:1.3;">
                Trans-Nzoia Community ICT Hub
              </h1>
              <p style="margin:6px 0 0;font-size:13px;color:rgba(255,255,255,0.6);">KCC Road, Kitale Town · Trans-Nzoia County</p>
            </td>
          </tr>

          <!-- Green success bar -->
          <tr>
            <td style="background:#16a34a;padding:14px 40px;text-align:center;">
              <p style="margin:0;font-size:15px;font-weight:700;color:#ffffff;">
                ✅ &nbsp;Enrollment Request Received
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="background:#ffffff;padding:40px 40px 32px;border-radius:0;">

              <p style="margin:0 0 18px;font-size:16px;color:#1e293b;">
                Dear <strong>{$firstName}</strong>,
              </p>

              <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
                Thank you for reaching out to the <strong>Trans-Nzoia Community ICT Hub</strong>.
                We have successfully received your enrollment request for:
              </p>

              <!-- Course highlight box -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
                <tr>
                  <td style="background:#f0f9ff;border-left:4px solid #0a192f;border-radius:0 8px 8px 0;padding:16px 20px;">
                    <p style="margin:0;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Course of Interest</p>
                    <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#0a192f;">{$course}</p>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
                Your application is currently being reviewed by our team. We will reach out to you
                <strong>within 24 hours</strong> via phone or email to confirm your enrollment and
                provide details about your class schedule.
              </p>

              <p style="margin:0 0 32px;font-size:15px;color:#475569;line-height:1.7;">
                In the meantime, if you have any questions, feel free to contact us directly:
              </p>

              <!-- Contact box -->
              <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 32px;">
                <tr>
                  <td style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;">
                    <p style="margin:0 0 8px;font-size:14px;color:#64748b;">📞 &nbsp;<strong>Phone:</strong> +254 727 474 015</p>
                    <p style="margin:0 0 8px;font-size:14px;color:#64748b;">✉️ &nbsp;<strong>Email:</strong> info@transnzoiaict.co.ke</p>
                    <p style="margin:0;font-size:14px;color:#64748b;">📍 &nbsp;<strong>Location:</strong> KCC Road, Kitale Town</p>
                  </td>
                </tr>
              </table>

              <p style="margin:0;font-size:15px;color:#475569;line-height:1.7;">
                We look forward to welcoming you and supporting your journey into the digital world.
              </p>

              <p style="margin:24px 0 0;font-size:15px;color:#1e293b;">
                Warm regards,<br>
                <strong>The Trans-Nzoia Community ICT Hub Team</strong><br>
                <span style="font-size:13px;color:#94a3b8;">Championed by Hon. Allan Chesang, CBS</span>
              </p>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f1f5f9;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                This is an automated confirmation email. Please do not reply directly to this message.<br>
                &copy; {$year} Trans-Nzoia Community ICT Hub. All rights reserved.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
}

// ─── Plain Text Fallback ──────────────────────────────────────────────────────
function buildEmailPlainText(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $year      = date('Y');

    return <<<TEXT
Trans-Nzoia Community ICT Hub
KCC Road, Kitale Town, Trans-Nzoia County
--------------------------------------------

Dear {$firstName},

Thank you for applying to the Trans-Nzoia Community ICT Hub.

We have received your enrollment request for: {$course}

Your application is currently being reviewed. We will contact you within 24 hours
via phone or email to confirm your enrollment and share your class schedule.

If you have any questions in the meantime:
- Phone:    +254 727 474 015
- Email:    info@transnzoiaict.co.ke
- Location: KCC Road, Kitale Town

We look forward to welcoming you.

Warm regards,
The Trans-Nzoia Community ICT Hub Team
Championed by Hon. Allan Chesang, CBS

--------------------------------------------
This is an automated email. Please do not reply directly.
© {$year} Trans-Nzoia Community ICT Hub. All rights reserved.
TEXT;
}
?>
