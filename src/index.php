<?php
require_once __DIR__ . '/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task_name'])) {
        addTask(trim($_POST['task_name']));
    }

    if (isset($_POST['mark_done'])) {
        markTaskAsCompleted($_POST['task_id'], true);
    }

    if (isset($_POST['mark_undone'])) {
        markTaskAsCompleted($_POST['task_id'], false);
    }

    if (isset($_POST['delete'])) {
        deleteTask($_POST['task_id']);
    }

    header("Location: index.php"); // avoid form resubmission
    exit();
}

$tasks = getAllTasks();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Task Planner</title>
    <style>
        body { font-family: Arial; margin: 30px; background: #f9f9f9; }
        h2 { color: #333; }
        form { margin-bottom: 20px; }
        input[type="text"], input[type="email"] { padding: 5px; width: 250px; }
        button { padding: 5px 10px; margin-left: 5px; }
        .task { margin: 10px 0; padding: 10px; background: #fff; border: 1px solid #ccc; }
        .done { text-decoration: line-through; color: gray; }
    </style>
</head>
<body>

<h2>📝 Task Planner</h2>

<!-- Add Task Form -->
<form method="POST">
    <input type="text" name="task_name" placeholder="Enter new task..." required>
    <button type="submit">Add Task</button>
</form>

<!-- Task List -->
<?php if (empty($tasks)): ?>
    <p>No tasks found.</p>
<?php else: ?>
    <?php foreach ($tasks as $task): ?>
        <div class="task">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                <span class="<?= $task['done'] ? 'done' : '' ?>">
                    <?= htmlspecialchars($task['name']) ?>
                </span>
                <?php if (!$task['done']): ?>
                    <button name="mark_done">Mark Done</button>
                <?php else: ?>
                    <button name="mark_undone">Undo</button>
                <?php endif; ?>
                <button name="delete" onclick="return confirm('Delete this task?');">Delete</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Email Subscription Form -->
<h2>📧 Subscribe for Task Reminders</h2>
<form method="POST" action="subscribe.php">
    <label for="email">Enter your email:</label>
    <input type="email" name="email" placeholder="you@example.com" required>
    <button type="submit">Subscribe</button>
</form>
<!-- Unsubscribe Form -->
<h2>Unsubscribe from Task Reminders</h2>
<form method="POST" action="unsubscribe.php">
    <label for="email">Enter your email:</label>
    <input type="email" name="email" required>
    <button type="submit">Unsubscribe</button>
</form>

</body>
</html>
