<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
            die("Внутренняя ошибка сервера.");
        }
    }
    return $pdo;
}

$pdo = getDB();

// HTTP Basic Auth
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="Админ-панель"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<div style="text-align:center;margin-top:50px;"><h1>Доступ запрещён</h1><p>Введите логин и пароль администратора.</p></div>';
    exit;
}
$auth_login = $_SERVER['PHP_AUTH_USER'];
$auth_pass = $_SERVER['PHP_AUTH_PW'];
$stmt = $pdo->prepare("SELECT password_hash FROM admin WHERE login = ?");
$stmt->execute([$auth_login]);
$admin = $stmt->fetch();
if (!$admin || !password_verify($auth_pass, $admin['password_hash'])) {
    header('WWW-Authenticate: Basic realm="Админ-панель"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<div style="text-align:center;margin-top:50px;"><h1>Неверный логин или пароль!</h1></div>';
    exit;
}

// Обработка действий
$messages = [];
$edit_errors = [];

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM application_interest WHERE application_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM application WHERE id = ?")->execute([$id]);
    $messages[] = '<div class="success-message">Анкета №' . $id . ' удалена</div>';
}

$edit_id = 0;
$edit_values = [];
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_values = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_values) {
        $int_stmt = $pdo->prepare("
            SELECT i.name FROM application_interest ai 
            JOIN interest i ON ai.interest_id = i.id 
            WHERE ai.application_id = ?
        ");
        $int_stmt->execute([$edit_id]);
        $edit_values['interests'] = $int_stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = (int)$_POST['edit_id'];
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $interests = $_POST['interests'] ?? [];

    $allowed_genders = ['male', 'female'];
    $allowed_interests = ['Марс', 'Луна', 'Юпитер', 'Сатурн', 'Чёрные дыры', 'Экзопланеты', 'Туманности', 'Космические миссии'];

    $has_error = false;
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF');
    }

    // Валидация
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name)) {
        $edit_errors['full_name'] = 'Только буквы и пробелы.';
        $has_error = true;
    }
    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        $edit_errors['phone'] = 'Некорректный телефон.';
        $has_error = true;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $edit_errors['email'] = 'Некорректный email.';
        $has_error = true;
    }
    if (empty($birth_date) || !strtotime($birth_date)) {
        $edit_errors['birth_date'] = 'Неверная дата.';
        $has_error = true;
    }
    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        $edit_errors['gender'] = 'Выберите пол.';
        $has_error = true;
    }
    if (strlen($biography) > 10000) {
        $edit_errors['biography'] = 'Биография слишком длинная.';
        $has_error = true;
    }
    if (!$contract_accepted) {
        $edit_errors['contract_accepted'] = 'Необходимо согласие.';
        $has_error = true;
    }
    if (empty($interests)) {
        $edit_errors['interests'] = 'Выберите хотя бы один интерес.';
        $has_error = true;
    } else {
        foreach ($interests as $int) {
            if (!in_array($int, $allowed_interests)) {
                $edit_errors['interests'] = 'Недопустимый интерес.';
                $has_error = true;
                break;
            }
        }
    }

    if ($has_error) {
        $edit_values = [
            'id' => $id,
            'full_name' => $full_name,
            'phone' => $phone,
            'email' => $email,
            'birth_date' => $birth_date,
            'gender' => $gender,
            'biography' => $biography,
            'contract_accepted' => $contract_accepted,
            'interests' => $interests
        ];
        $edit_id = $id;
        $messages[] = '<div class="error-message">Исправьте ошибки в форме.</div>';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                UPDATE application 
                SET full_name=?, phone=?, email=?, birth_date=?, gender=?, biography=?, contract_accepted=?
                WHERE id=?
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $id]);

            $pdo->prepare("DELETE FROM application_interest WHERE application_id = ?")->execute([$id]);

            $interest_map = [];
            $stmt = $pdo->query("SELECT id, name FROM interest");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $interest_map[$row['name']] = $row['id'];
            }
            $ins = $pdo->prepare("INSERT INTO application_interest (application_id, interest_id) VALUES (?, ?)");
            foreach ($interests as $int_name) {
                if (isset($interest_map[$int_name])) {
                    $ins->execute([$id, $interest_map[$int_name]]);
                }
            }

            $pdo->commit();
            $messages[] = '<div class="success-message">Анкета №' . $id . ' обновлена</div>';
            $edit_id = 0;
        } catch (Exception $e) {
            $pdo->rollBack();
            $messages[] = '<div class="error-message">Ошибка: ' . $e->getMessage() . '</div>';
        }
    }
}

// Список всех анкет
$applications = $pdo->query("
    SELECT a.*, GROUP_CONCAT(i.name SEPARATOR ', ') AS interests_list
    FROM application a
    LEFT JOIN application_interest ai ON a.id = ai.application_id
    LEFT JOIN interest i ON ai.interest_id = i.id
    GROUP BY a.id
    ORDER BY a.id DESC
")->fetchAll();

// Статистика
$stats = $pdo->query("
    SELECT i.name, COUNT(DISTINCT ai.application_id) AS cnt
    FROM interest i
    LEFT JOIN application_interest ai ON i.id = ai.interest_id
    GROUP BY i.id
    ORDER BY cnt DESC
")->fetchAll();

$all_interests = $pdo->query("SELECT name FROM interest ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель – Космический исследователь</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #0a0c24, #141946);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(8px);
            border-radius: 28px;
            padding: 25px 30px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        h1, h2 {
            color: #42dcff;
            font-weight: 400;
            letter-spacing: 1px;
        }
        h1 {
            font-size: 2.2rem;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .admin-badge {
            text-align: center;
            color: #aaa;
            margin-bottom: 2rem;
        }
        .success-message, .error-message {
            padding: 12px 18px;
            border-radius: 12px;
            margin: 15px 0;
            text-align: center;
        }
        .success-message {
            background: rgba(46, 125, 50, 0.2);
            border-left: 4px solid #2e7d32;
            color: #c8e6c9;
        }
        .error-message {
            background: rgba(198, 40, 40, 0.2);
            border-left: 4px solid #c62828;
            color: #ffcdd2;
        }
        .edit-form {
            background: rgba(0,0,0,0.4);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 40px;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }
        .form-group {
            margin-bottom: 0;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: #aaa;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            transition: 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #42dcff;
            background: rgba(255,255,255,0.12);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0 0;
        }
        .checkbox-group label {
            margin: 0;
            text-transform: none;
            color: #ddd;
        }
        .field-error {
            color: #ff8a80;
            font-size: 0.75rem;
            display: block;
            margin-top: 5px;
        }
        .btn-save {
            background: linear-gradient(45deg, #0066ff, #00ccff);
            border: none;
            padding: 12px 30px;
            border-radius: 40px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.2s;
            margin-top: 20px;
            display: inline-block;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,102,255,0.3);
        }
        .btn-cancel {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 12px 30px;
            border-radius: 40px;
            color: white;
            text-decoration: none;
            margin-left: 15px;
            transition: 0.2s;
            display: inline-block;
        }
        .btn-cancel:hover {
            background: rgba(255,255,255,0.2);
        }
        table {
            width: 100%;
            background: rgba(0,0,0,0.4);
            border-radius: 20px;
            overflow: hidden;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        th {
            background: rgba(0,160,227,0.2);
            color: #42dcff;
            font-weight: 500;
        }
        tr:hover td {
            background: rgba(255,255,255,0.03);
        }
        .actions a {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 0.85rem;
            transition: 0.2s;
            margin-right: 8px;
            display: inline-block;
        }
        .edit-link {
            background: rgba(66, 220, 255, 0.15);
            color: #42dcff;
            border: 1px solid rgba(66,220,255,0.3);
        }
        .edit-link:hover {
            background: rgba(66, 220, 255, 0.3);
        }
        .delete-link {
            background: rgba(255, 71, 87, 0.15);
            color: #ff8a80;
            border: 1px solid rgba(255,71,87,0.3);
        }
        .delete-link:hover {
            background: rgba(255, 71, 87, 0.3);
            color: #ffb3b3;
        }
        .stats-table {
            margin-top: 30px;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            color: #42dcff;
            text-decoration: none;
            border: 1px solid rgba(66,220,255,0.3);
            padding: 8px 20px;
            border-radius: 30px;
            transition: 0.2s;
        }
        .back-link a:hover {
            background: rgba(66,220,255,0.1);
        }
        @media (max-width: 768px) {
            .admin-container { padding: 15px; }
            th, td { padding: 8px 6px; font-size: 0.85rem; }
            .actions a { padding: 4px 8px; font-size: 0.7rem; }
            .form-grid { grid-template-columns: 1fr; }
        }
        .admin-container td,
        .admin-container th {
            color: #ffffff;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <h1> Админ-панель</h1>
    <div class="admin-badge">Авторизован как <strong><?= htmlspecialchars($auth_login) ?></strong></div>

    <?php foreach ($messages as $msg): ?><?= $msg ?><?php endforeach; ?>

    <?php if ($edit_id > 0 && !empty($edit_values)): ?>
        <div class="edit-form">
            <h2 style="margin-top:0;">Редактирование анкеты №<?= $edit_id ?></h2>
            <form method="POST">
                <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>ФИО</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($edit_values['full_name'] ?? '') ?>">
                        <?= isset($edit_errors['full_name']) ? '<span class="field-error">'.$edit_errors['full_name'].'</span>' : '' ?>
                    </div>
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($edit_values['phone'] ?? '') ?>">
                        <?= isset($edit_errors['phone']) ? '<span class="field-error">'.$edit_errors['phone'].'</span>' : '' ?>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($edit_values['email'] ?? '') ?>">
                        <?= isset($edit_errors['email']) ? '<span class="field-error">'.$edit_errors['email'].'</span>' : '' ?>
                    </div>
                    <div class="form-group">
                        <label>Дата рождения</label>
                        <input type="date" name="birth_date" value="<?= htmlspecialchars($edit_values['birth_date'] ?? '') ?>">
                        <?= isset($edit_errors['birth_date']) ? '<span class="field-error">'.$edit_errors['birth_date'].'</span>' : '' ?>
                    </div>
                    <div class="form-group">
                        <label>Пол</label>
                        <select name="gender">
                            <option value="male" <?= ($edit_values['gender']??'')==='male' ? 'selected' : '' ?>>Мужской</option>
                            <option value="female" <?= ($edit_values['gender']??'')==='female' ? 'selected' : '' ?>>Женский</option>
                        </select>
                        <?= isset($edit_errors['gender']) ? '<span class="field-error">'.$edit_errors['gender'].'</span>' : '' ?>
                    </div>
                    <div class="form-group">
                        <label>Космические интересы (выберите несколько)</label>
                        <select name="interests[]" multiple size="5">
                            <?php foreach ($all_interests as $int): ?>
                                <option value="<?= htmlspecialchars($int) ?>" <?= in_array($int, $edit_values['interests'] ?? []) ? 'selected' : '' ?>><?= htmlspecialchars($int) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= isset($edit_errors['interests']) ? '<span class="field-error">'.$edit_errors['interests'].'</span>' : '' ?>
                    </div>
                    <div class="form-group full-width">
                        <label>Биография</label>
                        <textarea name="biography" rows="5"><?= htmlspecialchars($edit_values['biography'] ?? '') ?></textarea>
                        <?= isset($edit_errors['biography']) ? '<span class="field-error">'.$edit_errors['biography'].'</span>' : '' ?>
                    </div>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="contract_accepted" value="1" id="contract" <?= !empty($edit_values['contract_accepted']) ? 'checked' : '' ?>>
                    <label for="contract">Я даю согласие на обработку персональных данных</label>
                </div>
                <?= isset($edit_errors['contract_accepted']) ? '<span class="field-error">'.$edit_errors['contract_accepted'].'</span>' : '' ?>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div style="margin-top:20px;">
                    <button type="submit" class="btn-save">Сохранить изменения</button>
                    <a href="admin.php" class="btn-cancel">Отмена</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <h2>Все заявки</h2>
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr><th>ID</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>Дата рожд.</th><th>Пол</th><th>Интересы</th><th>Действия</th></tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= $app['id'] ?></td>
                    <td><?= htmlspecialchars($app['full_name']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= $app['birth_date'] ?></td>
                    <td><?= $app['gender']==='male'?'Мужской':'Женский' ?></td>
                    <td><?= htmlspecialchars($app['interests_list'] ?? '—') ?></td>
                    <td class="actions">
                        <a href="admin.php?edit=<?= $app['id'] ?>" class="edit-link"> Редактировать</a>
                        <a href="admin.php?delete=<?= $app['id'] ?>" onclick="return confirm('Удалить анкету №<?= $app['id'] ?>?')" class="delete-link"> Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($applications)): ?>
                <tr><td colspan="8" style="text-align:center;">Пока нет ни одной заявки</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>Статистика по интересам</h2>
    <div style="overflow-x: auto;">
        <table class="stats-table">
            <thead><tr><th>Интерес</th><th>Количество выбравших</th></tr></thead>
            <tbody>
            <?php foreach ($stats as $s): ?>
                <tr><td><?= htmlspecialchars($s['name']) ?></td><td><strong><?= $s['cnt'] ?></strong></td></tr>
            <?php endforeach; ?>
            <?php if (empty($stats)): ?>
                <tr><td colspan="2">Нет данных</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="back-link">
        <a href="index.php">← Вернуться на главную страницу</a>
    </div>
</div>
</body>
</html>