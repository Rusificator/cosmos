<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['application_id'])) {
    header('Location: index.php');
    exit();
}


function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $user = 'u82457';
        $pass = '7777166';
        $name = 'u82457';
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die("Внутренняя ошибка сервера. Попробуйте позже.");
        }
    }
    return $pdo;
}


$errors = [];
$login_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF. Обновите страницу.');
    }
    $login_input = trim($_POST['login'] ?? '');
    $password_input = $_POST['password'] ?? '';
    if (empty($login_input) || empty($password_input)) {
        $errors[] = 'Введите логин и пароль';
    } else {
        $pdo = getDB(); // теперь PDO создастся
        $stmt = $pdo->prepare("SELECT id, password_hash FROM application WHERE login = ?");
        $stmt->execute([$login_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password_input, $user['password_hash'])) {
            $_SESSION['application_id'] = $user['id'];
            $_SESSION['user_login'] = $login_input;
            header('Location: index.php');
            exit();
        } else {
            $errors[] = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход – Космический исследователь</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0c24, #141946);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(255,255,255,0.1);
        }
        h1 {
            color: #42dcff;
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .error-message {
            background: rgba(255,71,87,0.2);
            color: #ff4757;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        input {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #0066ff, #00ccff);
            border: none;
            border-radius: 30px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: #42dcff;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 Вход в космический клуб</h1>
    <?php foreach ($errors as $err): ?>
        <div class="error-message"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
    <form method="post">
        <div class="form-group">
            <label>Логин</label>
            <input type="text" name="login" value="<?= htmlspecialchars($login_input) ?>" required>
        </div>
        <div class="form-group">
            <label>Пароль</label>
            <input type="password" name="password" required>
        </div>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
        <button type="submit">Войти</button>
    </form>
    <div class="back-link">
        <p>Ещё не получали логин и пароль?</p>
        <a href="index.php#anketa">← Вернуться к форме</a>
    </div>
</div>
</body>
</html>