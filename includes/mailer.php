<?php
// includes/mailer.php - sends the reset code.
// MAIL_MODE 'demo'  = no internet needed. The "email" appears on a simulated inbox page (demo_inbox.php).
// MAIL_MODE 'smtp'  = real Gmail via PHPMailer (needs internet). We can add this later if there is time.
// Load includes/auth.php BEFORE this file (it starts the session).

define('MAIL_MODE', 'demo');

function send_reset_email(string $to, string $name, string $pin): bool {
    if (MAIL_MODE === 'demo') {
        $_SESSION['demo_inbox'] = ['to' => $to, 'name' => $name, 'pin' => $pin, 'time' => date('g:i A')];
        return true;
    }
    return false;   // real SMTP sending goes here later
}
