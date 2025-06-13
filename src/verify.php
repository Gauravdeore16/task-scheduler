<?php
require_once 'functions.php';

$message = '';

// ✅ 1. Handle GET-based verification (via clickable email link)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $email = trim($_GET['email'] ?? '');
    $code  = trim($_GET['code'] ?? '');

    if ($email && $code && verifySubscription($email, $code)) {
        $message = "✅ Verification successful via link!";
    } elseif ($email && $code) {
        $message = "❌ Invalid or expired verification link.";
    }
}

// ✅ 2. Handle manual code entry via form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code'] ?? '');

    if ($email && $code) {
        if (verifySubscription($email, $code)) {
            $message = "✅ Verification successful! You'll now receive task reminders.";
        } else {
            $message = "❌ Invalid email or verification code.";
        }
    } else {
        $message = "⚠️ Please enter both email and code.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Subscription</title>
    <style>
        body { font-family: Arial; padding: 30px; background: #f0f0f0; }
        form { background: white; padding: 20px; border-radius: 8px; max-width: 400px; margin: auto; }
        input, button { margin: 10px 0; padding: 10px; width: 100%; }
        .msg { text-align: center; color: green; font-weight: bold; margin-bottom: 15px; }
        .error { color: red; }
    </style>
</head>
<body>

    <!-- Do not modify the ID of the heading -->
    <h2 id="verification-heading" style="text-align:center;">Subscription Verification</h2>

    <?php if ($message): ?>
        <p class="msg <?= str_contains($message, 'Invalid') || str_contains($message, '⚠️') ? 'error' : '' ?>">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <!-- Manual Verification Form -->
    <form method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="code">Verification Code:</label>
        <input type="text" name="code" required>

        <button type="submit">Verify</button>
    </form>

</body>
</html>
