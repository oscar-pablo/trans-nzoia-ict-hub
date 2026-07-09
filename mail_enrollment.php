<?php
/**
 * mail_enrollment.php
 * Sends confirmation, approval, and rejection emails related to student enrollment.
 *
 * Usage:
 *   require_once 'mail_enrollment.php';
 *   $result = sendEnrollmentConfirmation($firstName, $email, $course);
 *   $result = sendApprovalEmail($firstName, $email, $course);
 *   $result = sendRejectionEmail($firstName, $email, $course);
 *   // Each returns ['sent' => true] or ['sent' => false, 'error' => '...']
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/yz/vendor/autoload.php';
require_once __DIR__ . '/yz/mail_config.php';

/**
 * Shared helper: configures and returns a ready-to-send PHPMailer instance,
 * or throws so the caller's try/catch can format the error consistently.
 */
function buildMailer(string $studentEmail, string $firstName): PHPMailer
{
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;

    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress($studentEmail, $firstName);
    $mail->addReplyTo('info@transnzoiaict.co.ke', 'Trans-Nzoia ICT Hub');

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';

    return $mail;
}

/* ============================================================
   1. Initial "Application Received" Email
   ============================================================ */
function sendEnrollmentConfirmation(string $firstName, string $studentEmail, string $course): array
{
    if (empty(trim($studentEmail))) {
        return ['sent' => false, 'error' => 'No email address provided.'];
    }

    try {
        $mail = buildMailer($studentEmail, $firstName);
        $mail->Subject = 'Your Enrollment Request Has Been Received — Trans-Nzoia ICT Hub';
        $mail->Body    = buildReceivedHTML($firstName, $course);
        $mail->AltBody = buildReceivedPlainText($firstName, $course);

        $mail->send();
        return ['sent' => true];

    } catch (Exception $e) {
        return ['sent' => false, 'error' => $e->getMessage()];
    }
}

/* ============================================================
   2. Approval Email
   ============================================================ */
function sendApprovalEmail(string $firstName, string $studentEmail, string $course): array
{
    if (empty(trim($studentEmail))) {
        return ['sent' => false, 'error' => 'No email address provided.'];
    }

    try {
        $mail = buildMailer($studentEmail, $firstName);
        $mail->Subject = 'You\'re Enrolled! Next Steps — Trans-Nzoia ICT Hub';
        $mail->Body    = buildApprovalHTML($firstName, $course);
        $mail->AltBody = buildApprovalPlainText($firstName, $course);

        $mail->send();
        return ['sent' => true];

    } catch (Exception $e) {
        return ['sent' => false, 'error' => $e->getMessage()];
    }
}

/* ============================================================
   3. Rejection Email
   ============================================================ */
function sendRejectionEmail(string $firstName, string $studentEmail, string $course): array
{
    if (empty(trim($studentEmail))) {
        return ['sent' => false, 'error' => 'No email address provided.'];
    }

    try {
        $mail = buildMailer($studentEmail, $firstName);
        $mail->Subject = 'Update on Your Enrollment Application — Trans-Nzoia ICT Hub';
        $mail->Body    = buildRejectionHTML($firstName, $course);
        $mail->AltBody = buildRejectionPlainText($firstName, $course);

        $mail->send();
        return ['sent' => true];

    } catch (Exception $e) {
        return ['sent' => false, 'error' => $e->getMessage()];
    }
}

/* ============================================================
   Shared header/footer wrapper — used by all three templates
   ============================================================ */
function wrapEmailHTML(string $bannerColor, string $bannerText, string $bodyContent): string
{
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trans-Nzoia ICT Hub</title>
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

          <!-- Status bar -->
          <tr>
            <td style="background:{$bannerColor};padding:14px 40px;text-align:center;">
              <p style="margin:0;font-size:15px;font-weight:700;color:#ffffff;">{$bannerText}</p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="background:#ffffff;padding:40px 40px 32px;">
              {$bodyContent}
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f1f5f9;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
              <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.6;">
                This is an automated email. Please do not reply directly to this message.<br>
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

/* ── Contact box reused in every template ─────────────────────────────── */
function contactBoxHTML(): string
{
    return <<<HTML
    <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
      <tr>
        <td style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px 24px;">
          <p style="margin:0 0 8px;font-size:14px;color:#64748b;">📞 &nbsp;<strong>Phone:</strong> +254 727 474 015</p>
          <p style="margin:0 0 8px;font-size:14px;color:#64748b;">✉️ &nbsp;<strong>Email:</strong> info@transnzoiaict.co.ke</p>
          <p style="margin:0;font-size:14px;color:#64748b;">📍 &nbsp;<strong>Location:</strong> KCC Road, Kitale Town</p>
        </td>
      </tr>
    </table>
HTML;
}

/* ── Course highlight box reused in every template ───────────────────── */
function courseBoxHTML(string $course): string
{
    return <<<HTML
    <table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
      <tr>
        <td style="background:#f0f9ff;border-left:4px solid #0a192f;border-radius:0 8px 8px 0;padding:16px 20px;">
          <p style="margin:0;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;">Course of Interest</p>
          <p style="margin:4px 0 0;font-size:18px;font-weight:800;color:#0a192f;">{$course}</p>
        </td>
      </tr>
    </table>
HTML;
}

/* ============================================================
   Template 1: Application Received
   ============================================================ */
function buildReceivedHTML(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $courseBox = courseBoxHTML($course);
    $contact   = contactBoxHTML();

    $body = <<<HTML
      <p style="margin:0 0 18px;font-size:16px;color:#1e293b;">Dear <strong>{$firstName}</strong>,</p>
      <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
        Thank you for reaching out to the <strong>Trans-Nzoia Community ICT Hub</strong>.
        We have successfully received your enrollment request for:
      </p>
      {$courseBox}
      <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
        Your application is currently being reviewed by our team. We will reach out to you
        <strong>within 24 hours</strong> via phone or email to confirm your enrollment and
        provide details about your class schedule.
      </p>
      <p style="margin:0 0 32px;font-size:15px;color:#475569;line-height:1.7;">
        In the meantime, if you have any questions, feel free to contact us directly:
      </p>
      {$contact}
      <p style="margin:0;font-size:15px;color:#475569;line-height:1.7;">
        We look forward to welcoming you and supporting your journey into the digital world.
      </p>
      <p style="margin:24px 0 0;font-size:15px;color:#1e293b;">
        Warm regards,<br>
        <strong>The Trans-Nzoia Community ICT Hub Team</strong><br>
        <span style="font-size:13px;color:#94a3b8;">Championed by Hon. Allan Chesang, CBS</span>
      </p>
HTML;

    return wrapEmailHTML('#16a34a', '✅ &nbsp;Enrollment Request Received', $body);
}

function buildReceivedPlainText(string $firstName, string $course): string
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

/* ============================================================
   Template 2: Approved — invite them to visit and enroll
   ============================================================ */
function buildApprovalHTML(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $courseBox = courseBoxHTML($course);
    $contact   = contactBoxHTML();

    $body = <<<HTML
      <p style="margin:0 0 18px;font-size:16px;color:#1e293b;">Dear <strong>{$firstName}</strong>,</p>
      <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
        Great news! Your enrollment application to the <strong>Trans-Nzoia Community ICT Hub</strong>
        has been <strong style="color:#16a34a;">approved</strong> for:
      </p>
      {$courseBox}
      <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
        Please visit our office at your earliest convenience to complete your enrollment
        and begin your classes. Kindly bring the following with you:
      </p>
      <ul style="margin:0 0 24px;padding-left:20px;font-size:15px;color:#475569;line-height:1.9;">
        <li>Your National ID, Birth Certificate, or other identification provided during application</li>
        <li>One passport-size photo</li>
        <li>Any applicable registration fee (please contact us below if you're unsure of the amount)</li>
      </ul>
      <p style="margin:0 0 32px;font-size:15px;color:#475569;line-height:1.7;">
        If you have any questions before your visit, feel free to reach us directly:
      </p>
      {$contact}
      <p style="margin:0;font-size:15px;color:#475569;line-height:1.7;">
        We're excited to welcome you to the Hub and support your journey into the digital world.
      </p>
      <p style="margin:24px 0 0;font-size:15px;color:#1e293b;">
        Warm regards,<br>
        <strong>The Trans-Nzoia Community ICT Hub Team</strong><br>
        <span style="font-size:13px;color:#94a3b8;">Championed by Hon. Allan Chesang, CBS</span>
      </p>
HTML;

    return wrapEmailHTML('#16a34a', '🎉 &nbsp;Enrollment Approved', $body);
}

function buildApprovalPlainText(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $year      = date('Y');

    return <<<TEXT
Trans-Nzoia Community ICT Hub
KCC Road, Kitale Town, Trans-Nzoia County
--------------------------------------------

Dear {$firstName},

Great news! Your enrollment application has been APPROVED for: {$course}

Please visit our office at your earliest convenience to complete your enrollment
and begin your classes. Kindly bring the following with you:
- Your National ID, Birth Certificate, or other identification provided during application
- One passport-size photo
- Any applicable registration fee

If you have any questions before your visit:
- Phone:    +254 727 474 015
- Email:    info@transnzoiaict.co.ke
- Location: KCC Road, Kitale Town

We're excited to welcome you to the Hub.

Warm regards,
The Trans-Nzoia Community ICT Hub Team
Championed by Hon. Allan Chesang, CBS

--------------------------------------------
This is an automated email. Please do not reply directly.
© {$year} Trans-Nzoia Community ICT Hub. All rights reserved.
TEXT;
}

/* ============================================================
   Template 3: Not approved — warm, encouraging tone
   ============================================================ */
function buildRejectionHTML(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $contact   = contactBoxHTML();

    $body = <<<HTML
      <p style="margin:0 0 18px;font-size:16px;color:#1e293b;">Dear <strong>{$firstName}</strong>,</p>
      <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
        Thank you for your interest in the <strong>Trans-Nzoia Community ICT Hub</strong> and for
        applying for our <strong>{$course}</strong> program.
      </p>
      <p style="margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;">
        After reviewing your application, we're unable to confirm your enrollment for this
        intake. This does not reflect on you personally — intake capacity, scheduling, or
        documentation are common reasons applications can't be confirmed right away.
      </p>
      <p style="margin:0 0 32px;font-size:15px;color:#475569;line-height:1.7;">
        We'd genuinely welcome you to apply again for our next intake, or to visit us in person
        so we can guide you toward the best option for your situation. Please don't hesitate
        to reach out — we're here to help:
      </p>
      {$contact}
      <p style="margin:0;font-size:15px;color:#475569;line-height:1.7;">
        We hope to welcome you to the Hub soon.
      </p>
      <p style="margin:24px 0 0;font-size:15px;color:#1e293b;">
        Warm regards,<br>
        <strong>The Trans-Nzoia Community ICT Hub Team</strong><br>
        <span style="font-size:13px;color:#94a3b8;">Championed by Hon. Allan Chesang, CBS</span>
      </p>
HTML;

    return wrapEmailHTML('#64748b', 'Application Update', $body);
}

function buildRejectionPlainText(string $firstName, string $course): string
{
    $firstName = htmlspecialchars($firstName);
    $course    = htmlspecialchars($course);
    $year      = date('Y');

    return <<<TEXT
Trans-Nzoia Community ICT Hub
KCC Road, Kitale Town, Trans-Nzoia County
--------------------------------------------

Dear {$firstName},

Thank you for your interest in the Trans-Nzoia Community ICT Hub and for applying
for our {$course} program.

After reviewing your application, we're unable to confirm your enrollment for this
intake. This does not reflect on you personally — intake capacity, scheduling, or
documentation are common reasons applications can't be confirmed right away.

We'd genuinely welcome you to apply again for our next intake, or to visit us in
person so we can guide you toward the best option for your situation.

- Phone:    +254 727 474 015
- Email:    info@transnzoiaict.co.ke
- Location: KCC Road, Kitale Town

We hope to welcome you to the Hub soon.

Warm regards,
The Trans-Nzoia Community ICT Hub Team
Championed by Hon. Allan Chesang, CBS

--------------------------------------------
This is an automated email. Please do not reply directly.
© {$year} Trans-Nzoia Community ICT Hub. All rights reserved.
TEXT;
}
?>