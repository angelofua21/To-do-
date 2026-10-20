<?php
// 1. Start the Session to store tasks (Server-side State Management)
session_start();

if (!isset($_SESSION['my_tasks'])) {
    $_SESSION['my_tasks'] = [];
}

// Get current filter (default is 'all')
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// 2. LOGIC ACTIONS

// Add New Task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $text = trim($_POST['task_text'] ?? '');
    if (!empty($text)) {
        $_SESSION['my_tasks'][] = [
            'id' => time() . rand(100, 999),
            'text' => $text,
            'completed' => false
        ];
    }
    header("Location: index.php?filter=" . urlencode($filter));
    exit;
}

// Toggle Task Status (Complete / Incomplete)
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $id = $_GET['id'];
    foreach ($_SESSION['my_tasks'] as &$task) {
        if ($task['id'] == $id) {
            $task['completed'] = !$task['completed'];
            break;
        }
    }
    header("Location: index.php?filter=" . urlencode($filter));
    exit;
}

// Delete a Task
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $_SESSION['my_tasks'] = array_values(array_filter($_SESSION['my_tasks'], function ($task) use ($id) {
        return $task['id'] != $id;
    }));
    header("Location: index.php?filter=" . urlencode($filter));
    exit;
}

// Delete All Completed Tasks
if (isset($_GET['action']) && $_GET['action'] === 'clear_completed') {
    $_SESSION['my_tasks'] = array_values(array_filter($_SESSION['my_tasks'], function ($task) {
        return !$task['completed'];
    }));
    header("Location: index.php?filter=" . urlencode($filter));
    exit;
}

// 3. FILTER LOGIC & DATA PREPARATION
$visibleTasks = array_filter($_SESSION['my_tasks'], function ($task) use ($filter) {
    if ($filter === 'active') return !$task['completed'];
    if ($filter === 'completed') return $task['completed'];
    return true;
});

// Count remaining active tasks
$activeCount = count(array_filter($_SESSION['my_tasks'], function ($task) {
    return !$task['completed'];
}));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do App (PHP)</title>

    <style>
        /* 1. CSS Variables & Reset */
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.1);
            --danger: #ef4444;
            --radius: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            min-height: 100vh;
            padding: 40px 16px;
        }

        /* 2. Main Card Container */
        .card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: var(--radius);
            width: 100%;
            max-width: 420px;
            border: 1px solid var(--border-color);
            height: fit-content;
        }

        /* 3. Header & Stats */
        h1 {
            font-size: 2.0rem;
            margin-bottom: 4px;
            text-align: center;
        }

        #stats {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 20px;
            text-align: center;
        }

        /* 4. Input & Form Controls */
        .input-group {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }

        input[type="text"] {
            flex: 1;
            padding: 10px 13px;
            background: var(--bg-body);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-main);
            outline: none;
        }

        input[type="text"]:focus {
            border-color: var(--primary);
        }

        /* 5. Buttons */
        button,
        .btn {
            padding: 10px 16px;
            background: var(--primary);
            color: rgb(246, 244, 244);
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        button:hover,
        .btn:hover {
            background: var(--primary-hover);
        }

        /* 6. Filter Controls */
        .filters {
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
        }

        .filters a {
            flex: 1;
            background: transparent;
            color: var(--text-muted);
            padding: 6px;
            font-size: 0.85rem;
            text-align: center;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
        }

        .filters a.active {
            background: var(--primary);
            color: white;
        }

        /* 7. Task Item List */
        ul {
            list-style: none;
            margin-bottom: 16px;
            max-height: 300px;
            overflow-y: auto;
        }

        li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            margin-bottom: 8px;
        }

        li a.task-text {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-main);
            text-decoration: none;
            flex: 1;
        }

        li a.task-text.done {
            text-decoration: line-through;
            color: var(--text-muted);
        }

        .delete-btn {
            background: transparent;
            color: var(--text-muted);
            padding: 4px 8px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .delete-btn:hover {
            color: var(--danger);
        }

        /* 8. Footer Action & Empty State */
        .clear-btn {
            display: block;
            width: 100%;
            background: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            text-align: center;
            padding: 10px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
        }

        .clear-btn:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: transparent;
        }

        .empty-state {
            text-align: center;
            padding: 16px 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <!-- Main container for the app -->
    <div class="card">

        <!-- App title -->
        <h1>My Tasks</h1>

        <!-- Shows the task count -->
        <div id="stats"><?= $activeCount ?> item<?= $activeCount === 1 ? '' : 's' ?> left</div>

        <!-- Input field and button to add new tasks -->
        <form action="index.php?filter=<?= urlencode($filter) ?>" method="POST" class="input-group">
            <input type="hidden" name="action" value="add">
            <input type="text" name="task_text" placeholder="Add a new task..." required autocomplete="off">
            <button type="submit">Add</button>
        </form>

        <!-- Buttons to filter tasks by status -->
        <div class="filters">
            <a href="index.php?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
            <a href="index.php?filter=active" class="<?= $filter === 'active' ? 'active' : '' ?>">Active</a>
            <a href="index.php?filter=completed" class="<?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
        </div>

        <!-- List where tasks will appear -->
        <ul id="task-list">
            <?php if (empty($visibleTasks)): ?>

            <?php else: ?>
                <?php foreach ($visibleTasks as $task): ?>
                    <li>
                        <a href="index.php?action=toggle&id=<?= $task['id'] ?>&filter=<?= urlencode($filter) ?>"
                            class="task-text <?= $task['completed'] ? 'done' : '' ?>">
                            <span><?= $task['completed'] ? '☑' : '☐' ?></span>
                            <span><?= htmlspecialchars($task['text']) ?></span>
                        </a>
                        <a href="index.php?action=delete&id=<?= $task['id'] ?>&filter=<?= urlencode($filter) ?>"
                            class="delete-btn">x</a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <!-- Button to delete all completed tasks -->

    </div>

</body>

</html>