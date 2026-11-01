<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

// Only exists in demo mode, and only if a code was just requested in this browser session
if (MAIL_MODE !== 'demo' || empty($_SESSION['demo_inbox'])) { http_response_code(404); die('Nothing to show.'); }
$mail = $_SESSION['demo_inbox'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo Inbox</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="center">
  <div class="email-card">
    <p class="note">DEMO INBOX: simulates the email so the system works without internet.</p>
    <p><strong>From:</strong> Mentality &lt;noreply@mentality.local&gt;<br>
       <strong>To:</strong> <?= e($mail['to']) ?><br>
       <strong>Time:</strong> <?= e($mail['time']) ?></p>
    <h2>Password Reset Code</h2>
    <p>Hello <?= e($mail['name']) ?>,</p>
    <p>Your Mentality verification code is:</p>
    <div class="pin-display"><?= e($mail['pin']) ?></div>
    <p>This code will expire in 10 minutes. If you didn't request this, you can ignore this email.</p>
    <p>&mdash; Mentality Team</p>
  </div>
</div>
</body>
</html>
