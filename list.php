<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$host = 'localhost';
$db = 'todo_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$user_id = (int) $_SESSION['user_id'];

// Fetch username for greeting
$username = '';
$result = $conn->query("SELECT username FROM users WHERE id = $user_id LIMIT 1");
if ($row = $result->fetch_assoc()) {
    $username = htmlspecialchars($row['username']);
}

// Handle AJAX POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'update_order' && isset($_POST['order'])) {
        // Update sort_order in database
        $order = $_POST['order']; // expected as JSON array of todo IDs
        $ids = json_decode($order, true);
        if (is_array($ids)) {
            foreach ($ids as $sort_order => $id) {
                $id = (int)$id;
                $sort_order = (int)$sort_order;
                $conn->query("UPDATE todos SET sort_order = $sort_order WHERE id = $id AND user_id = $user_id");
            }
        }
        exit;
    }
    if (isset($_POST['content']) && !isset($_POST['id'])) {
        $content = $conn->real_escape_string(trim($_POST['content']));
        if ($content !== '') {
            // Insert with max sort_order + 1
            $res = $conn->query("SELECT MAX(sort_order) AS max_order FROM todos WHERE user_id = $user_id");
            $max_order = 0;
            if ($row = $res->fetch_assoc()) {
                $max_order = (int)$row['max_order'] + 1;
            }
            $conn->query("INSERT INTO todos (content, user_id, sort_order) VALUES ('$content', $user_id, $max_order)");
        }
        exit;
    } elseif (isset($_POST['id']) && isset($_POST['content'])) {
        $id = (int) $_POST['id'];
        $content = $conn->real_escape_string(trim($_POST['content']));
        if ($content !== '') {
            $conn->query("UPDATE todos SET content = '$content' WHERE id = $id AND user_id = $user_id");
        }
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM todos WHERE id = $id AND user_id = $user_id");
    exit;
}

if (isset($_GET['fetch'])) {
    // Changed ORDER BY to sort_order ASC for drag order
    $result = $conn->query("SELECT * FROM todos WHERE user_id = $user_id ORDER BY sort_order ASC");
    $todos = [];
    while ($row = $result->fetch_assoc()) {
        $todos[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($todos);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Bukas na 'to</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            background: #f9fafb;
            font-family: 'Segoe UI', sans-serif;
            max-width: 600px;
            margin: auto;
            padding: 40px;
            color: #333;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #111;
        }

        form {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        input[type="text"] {
            flex: 1;
            padding: 14px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 10px;
            outline: none;
        }

        button {
            padding: 14px 20px;
            border: none;
            background: #6366f1;
            color: white;
            font-size: 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s, filter 0.2s;
        }

        button:hover,
        .toggle-password:hover {
            filter: brightness(0.85);
            transition: background 0.2s, filter 0.2s;
        }

        .register-btn {
            background: #10b981;
        }

        .register-btn:hover {
            background: #059669;
            filter: brightness(0.95);
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: #888;
            font-size: 1.1em;
            padding: 0 4px;
            transition: color 0.2s, filter 0.2s;
        }

        .toggle-password:hover {
            color: #333;
            filter: brightness(0.7);
        }

        .toggle-password:focus {
            outline: none;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            background: white;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            cursor: grab;
        }

        li.dragging {
            opacity: 0.5;
        }

        .task-text {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .checkbox {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 4px;
            position: relative;
            cursor: pointer;
        }

        .checkbox:checked::before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 14px;
            position: absolute;
            top: 1px;
            left: 3px;
            color: #10b981;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .delete-btn,
        .edit-btn {
            font-size: 1.1em;
            background: none;
            border: none;
            cursor: pointer;
            padding: 1em;
        }

        .edit-btn i {
            color: #3b82f6;
            transition: color 0.5s ease;
        }

        .edit-btn:hover i {
            color: #1d4ed8;
        }

        .delete-btn i {
            color: #ef4444;
            transition: color 0.5s ease;
        }

        .delete-btn:hover i {
            color: #b91c1c;
        }

        .edit-input {
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            flex: 1;
        }
    </style>
</head>

<body>

    <h2>
        <?php
        echo "Hello, <b>$username</b><br>";
        echo "Bukas mo na ito gawin";
        ?>

    </h2>

    <form id="todoForm">
        <input type="text" name="content" id="content" placeholder="Add a new task..." required />
        <button type="submit"><i class="fas fa-plus"></i></button>
        <button type="button" class="btn-danger" style="background-color: #dc3545!important;"
            onclick="window.location.href='index.php'">
            <i class="fas fa-sign-out-alt"></i>
        </button>

    </form>

    <ul id="todoList"></ul>

    <script>
        const todoForm = document.getElementById('todoForm');
        const contentInput = document.getElementById('content');
        const todoList = document.getElementById('todoList');

        let draggedEl = null;

        function loadTodos() {
            fetch('list.php?fetch=1')
                .then(res => res.json())
                .then(data => {
                    todoList.innerHTML = '';
                    data.forEach(todo => {
                        const li = document.createElement('li');
                        li.draggable = true;
                        li.dataset.id = todo.id;

                        const taskDiv = document.createElement('div');
                        taskDiv.className = 'task-text';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.className = 'checkbox';

                        const span = document.createElement('span');
                        span.textContent = todo.content;

                        checkbox.addEventListener('change', () => {
                            span.style.textDecoration = checkbox.checked ? 'line-through' : 'none';
                            span.style.color = checkbox.checked ? '#aaa' : '#333';
                        });

                        taskDiv.appendChild(checkbox);
                        taskDiv.appendChild(span);

                        const actions = document.createElement('div');
                        actions.className = 'actions';

                        const editBtn = document.createElement('button');
                        editBtn.className = 'edit-btn';
                        editBtn.innerHTML = '<i class="fas fa-edit"></i>';

                        const deleteBtn = document.createElement('button');
                        deleteBtn.className = 'delete-btn';
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';

                        // Edit mode toggle
                        editBtn.onclick = () => {
                            if (editBtn.innerHTML.includes('fa-edit')) {
                                const input = document.createElement('input');
                                input.type = 'text';
                                input.value = span.textContent;
                                input.className = 'edit-input';
                                taskDiv.replaceChild(input, span);
                                editBtn.innerHTML = '<i class="fas fa-save"></i>';
                            } else {
                                const input = taskDiv.querySelector('input.edit-input');
                                if (input.value.trim() !== '') {
                                    // Save to DB
                                    fetch('list.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `id=${encodeURIComponent(li.dataset.id)}&content=${encodeURIComponent(input.value)}`
                                    }).then(() => {
                                        span.textContent = input.value;
                                        taskDiv.replaceChild(span, input);
                                        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                                    });
                                }
                            }
                        };

                        // Delete
                        deleteBtn.onclick = () => {
                            if (confirm('Delete this task?')) {
                                fetch(`list.php?delete=${li.dataset.id}`)
                                    .then(() => loadTodos());
                            }
                        };

                        actions.appendChild(editBtn);
                        actions.appendChild(deleteBtn);

                        li.appendChild(taskDiv);
                        li.appendChild(actions);

                        // Drag events
                        li.addEventListener('dragstart', (e) => {
                            draggedEl = li;
                            li.classList.add('dragging');
                            e.dataTransfer.effectAllowed = 'move';
                        });
                        li.addEventListener('dragend', () => {
                            draggedEl = null;
                            li.classList.remove('dragging');
                        });
                        li.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            const dragging = todoList.querySelector('.dragging');
                            if (li === dragging) return;

                            // Insert before or after based on mouse position
                            const bounding = li.getBoundingClientRect();
                            const offset = e.clientY - bounding.top;
                            if (offset > bounding.height / 2) {
                                todoList.insertBefore(dragging, li.nextSibling);
                            } else {
                                todoList.insertBefore(dragging, li);
                            }
                        });

                        todoList.appendChild(li);
                    });
                });
        }

        // Save new task
        todoForm.addEventListener('submit', e => {
            e.preventDefault();
            const content = contentInput.value.trim();
            if (!content) return;
            fetch('list.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `content=${encodeURIComponent(content)}`
            }).then(() => {
                contentInput.value = '';
                loadTodos();
            });
        });

        // Save order on drag end (mouse up on list)
        todoList.addEventListener('dragend', () => {
            const order = [...todoList.children].map(li => li.dataset.id);
            fetch('list.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_order&order=${encodeURIComponent(JSON.stringify(order))}`
            });
        });

        loadTodos();
    </script>

</body>

</html>
