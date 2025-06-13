<?php

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function addTask(string $task_name): bool {
    $file = __DIR__ . '/tasks.txt';
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) === 3 && strcasecmp(trim($parts[1]), trim($task_name)) === 0) {
                return false; // Duplicate task
            }
        }
    }
    $task_id = uniqid();
    $task_line = $task_id . '|' . $task_name . '|0' . PHP_EOL;
    return file_put_contents($file, $task_line, FILE_APPEND | LOCK_EX) !== false;
}

function getAllTasks(): array {
    $file = __DIR__ . '/tasks.txt';
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $tasks = [];

    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) === 3) {
            $tasks[] = ['id' => $parts[0], 'name' => $parts[1], 'done' => $parts[2] === '1'];
        }
    }
    return $tasks;
}

function markTaskAsCompleted(string $task_id, bool $is_completed): bool {
    $file = __DIR__ . '/tasks.txt';
    if (!file_exists($file)) return false;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $updated = false;

    foreach ($lines as &$line) {
        $parts = explode('|', $line);
        if (count($parts) === 3 && $parts[0] === $task_id) {
            $parts[2] = $is_completed ? '1' : '0';
            $line = implode('|', $parts);
            $updated = true;
            break;
        }
    }
    return $updated && file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL) !== false;
}

function deleteTask(string $task_id): bool {
    $file = __DIR__ . '/tasks.txt';
    if (!file_exists($file)) return false;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_lines = array_filter($lines, fn($line) => explode('|', $line)[0] !== $task_id);
    return file_put_contents($file, implode(PHP_EOL, $new_lines) . PHP_EOL) !== false;
}

function generateVerificationCode(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function subscribeEmail(string $email): bool {
    $file = __DIR__ . '/pending_subscriptions.txt';

    // ✅ Ensure file exists
    if (!file_exists($file)) {
        touch($file);  // Create an empty file if not present
    }

    $code = generateVerificationCode();
    $entry = $email . '|' . $code . PHP_EOL;

    if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) === false) return false;

    $subject = "Verify your subscription";
    $link = "http://localhost:8000/verify.php?email=" . urlencode($email) . "&code=" . urlencode($code);
    $message = "Click the link to verify your subscription:<br><a href='$link'>$link</a><br><br>Or manually enter this code: <b>$code</b>";

    return sendTaskEmail($email, [], $subject, $message);
}


function verifySubscription(string $email, string $code): bool {
    $pending_file = __DIR__ . '/pending_subscriptions.txt';
    $subscribers_file = __DIR__ . '/subscribers.txt';
    if (!file_exists($pending_file)) return false;

    $lines = file($pending_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_lines = [];
    $verified = false;

    foreach ($lines as $line) {
        [$saved_email, $saved_code] = explode('|', $line);
        if (trim($saved_email) === trim($email) && trim($saved_code) === trim($code)) {
            file_put_contents($subscribers_file, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
            $verified = true;
        } else {
            $new_lines[] = $line;
        }
    }

    file_put_contents($pending_file, implode(PHP_EOL, $new_lines) . PHP_EOL);
    return $verified;
}

function unsubscribeEmail(string $email): bool {
    $file = __DIR__ . '/subscribers.txt';
    if (!file_exists($file)) return false;

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_lines = array_filter($lines, fn($line) => trim($line) !== trim($email));

    return file_put_contents($file, implode(PHP_EOL, $new_lines) . PHP_EOL) !== false;
}

function sendTaskReminders(): void {
    $file = __DIR__ . '/subscribers.txt';
    if (!file_exists($file)) return;
    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $pending_tasks = array_filter(getAllTasks(), fn($task) => !$task['done']);

    foreach ($emails as $email) {
        sendTaskEmail($email, $pending_tasks);
    }
}

function sendTaskEmail(string $email, array $pending_tasks, string $custom_subject = '', string $custom_body = ''): bool {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gauravdeore1631@gmail.com';
        $mail->Password = 'slopijwkdkonxwvm'; // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('gauravdeore1631@gmail.com', 'Task Planner');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $custom_subject ?: 'Task Planner - Your Pending Tasks';

        if ($custom_body) {
            $body = $custom_body;
        } else {
            $body = '<h3>Your pending tasks:</h3><ul>';
            foreach ($pending_tasks as $task) {
                $body .= '<li>' . htmlspecialchars($task['name']) . '</li>';
            }
            $body .= '</ul>';
            $unsubscribe_link = 'http://localhost:8000/unsubscribe.php?email=' . urlencode($email);
            $body .= "<p style='margin-top:20px;'>To unsubscribe, click <a href='$unsubscribe_link'>here</a>.</p>";
        }

        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}
