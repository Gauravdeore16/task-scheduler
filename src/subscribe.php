<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email address.";
        echo "<br><a href='index.php'>Back to Task Planner</a>";
        exit;
    }

    // Check if already subscribed
    $subscribersFile = __DIR__ . '/subscribers.txt';
    $existingSubscribers = file_exists($subscribersFile) ? file($subscribersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if (in_array($email, $existingSubscribers)) {
        echo "You are already subscribed!";
        echo "<br><a href='index.php'>Back to Task Planner</a>";
        exit;
    }

    // Call the function to add to pending_subscriptions and send mail
    $success = subscribeEmail($email);

    if ($success) {
        echo "Verification email sent! Please check your inbox to confirm.";
    } else {
        echo "Something went wrong. Please try again later.";
    }

    echo "<br><a href='index.php'>Back to Task Planner</a>";
} else {
    header("Location: index.php");
    exit;
}
