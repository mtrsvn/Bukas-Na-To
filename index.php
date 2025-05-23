<?php
session_start();

$host = 'containers-abcde.railway.app';  // Replace with your actual public hostname
$port = 3306;
$db = 'railway';                         // Or 'todo_db' if that’s your DB name
$user = 'root';
$pass = 'tAeaHNSsmyeqwZTTKSxazSRspYHVgDvo';

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result && $row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            header("Location: list.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            background: #f9fafb;
            font-family: 'Segoe UI', sans-serif;
            max-width: 400px;
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
            flex-direction: column;
            gap: 15px;
        }

        input[type="text"],
        input[type="password"] {
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
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

        .error {
            color: #ef4444;
            text-align: center;
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            flex: 1;
            padding-right: 38px;
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
    <h2>Login</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <input type="text" name="username" placeholder="Username" required autofocus>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <button type="button" class="toggle-password" tabindex="-1" onclick="togglePassword('password', this)">
                <i class="fa-regular fa-eye"></i>
            </button>
        </div>
        <button type="submit">Login</button>
    </form>
    <form action="register.php" method="get" style="margin-top:10px;">
        <button type="submit" class="register-btn">Register</button>
    </form>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>