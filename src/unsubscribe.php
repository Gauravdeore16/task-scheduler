<?php
require_once 'functions.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (unsubscribeEmail($email)) {
            $message = "Successfully unsubscribed: $email";
        } else {
            $message = "Email not found or already unsubscribed: $email";
        }
    } else {
        $message = "Invalid email address.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Unsubscribe</title>
</head>
<body>
    <!-- Do not modify the ID of the heading -->
    <h2 id="unsubscription-heading">Unsubscribe from Task Updates</h2>

    <?php if ($message): ?>
        <p><?= htmlspecialchars($message) ?></p>
        <p><a href="index.php">Back to Task Planner</a></p>
    <?php else: ?>
        <form method="POST">
            <label for="email">Enter your email to unsubscribe:</label><br>
            <input type="email" name="email" id="email" required><br><br>
            <button type="submit">Unsubscribe</button>
        </form>
    <?php endif; ?>
</body>
</html>
