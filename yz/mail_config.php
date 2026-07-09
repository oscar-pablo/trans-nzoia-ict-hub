<?php
/**
 * mail_config.php
 * Central email configuration for Trans-Nzoia Community ICT Hub.
 *
 * ─────────────────────────────────────────────
 *  SWITCH ENVIRONMENTS:
 *  Change APP_ENV below to either 'local' or 'production'
 * ─────────────────────────────────────────────
 */

define('APP_ENV', 'local'); // ← change to 'production' when going live

// ─── LOCAL (Mailtrap) ────────────────────────────────────────────────────────
// Emails never actually send — they appear in your Mailtrap inbox for testing.
// Get these from: https://mailtrap.io → your inbox → SMTP Settings → PHPMailer
if (APP_ENV === 'local') {
    define('MAIL_HOST',       'sandbox.smtp.mailtrap.io');  // Mailtrap host
    define('MAIL_PORT',       2525);                         // Mailtrap port
    define('MAIL_USERNAME',   '58d48a0baa4911');     // ← replace
    define('MAIL_PASSWORD',   'fe2e262a1c1030');     // ← replace
    define('MAIL_ENCRYPTION', 'tls');
    define('MAIL_FROM_EMAIL', 'noreply@transnzoiaict.co.ke');
    define('MAIL_FROM_NAME',  'Trans-Nzoia Community ICT Hub');
}

// ─── PRODUCTION (Real SMTP) ──────────────────────────────────────────────────
// Option A: Gmail (requires an App Password — NOT your normal Gmail password)
//   → Google Account → Security → 2-Step Verification → App Passwords → generate one
// Option B: Hosting mail (e.g. cPanel / Namecheap / Hostinger)
//   → use the SMTP details your host gives you
if (APP_ENV === 'production') {
    define('MAIL_HOST',       'smtp.gmail.com');             // or your host's SMTP
    define('MAIL_PORT',       587);
    define('MAIL_USERNAME',   '58d48a0baa4911');             // ← replace
    define('MAIL_PASSWORD',   'fe2e262a1c1030');          // ← replace (App Password)
    define('MAIL_ENCRYPTION', 'tls');
    define('MAIL_FROM_EMAIL', 'noreply@transnzoiaict.co.ke');// or your@gmail.com
    define('MAIL_FROM_NAME',  'Trans-Nzoia Community ICT Hub');
}
?>
