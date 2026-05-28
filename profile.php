<?php
// profile.php – личный кабинет пользователя
session_start();

if (!isset($_SESSION['application_id'])) {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['application_id'];

// Подключение к БД
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

$pdo = getDB();

// Получаем данные пользователя
$stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Получаем выбранные интересы
$stmt = $pdo->prepare("
    SELECT i.name FROM application_interest ai 
    JOIN interest i ON ai.interest_id = i.id 
    WHERE ai.application_id = ?
");
$stmt->execute([$user_id]);
$user_interests = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Список всех интересов для формы
$all_interests = $pdo->query("SELECT name FROM interest ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

$allowed_genders = ['male', 'female'];
$allowed_interests = ['Марс', 'Луна', 'Юпитер', 'Сатурн', 'Чёрные дыры', 'Экзопланеты', 'Туманности', 'Космические миссии'];

$message = '';
$errors = [];

// Обработка POST (обновление профиля)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF. Обновите страницу.');
    }

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $interests = $_POST['interests'] ?? [];

    // Валидация
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name) || strlen($full_name) > 150) {
        $errors['full_name'] = 'ФИО должно содержать только буквы и пробелы (макс. 150 символов).';
    }
    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        $errors['phone'] = 'Телефон должен содержать от 6 до 12 символов (цифры, +, -, (, ), пробел).';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email.';
    }
    if (empty($birth_date)) {
        $errors['birth_date'] = 'Дата рождения обязательна.';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            $errors['birth_date'] = 'Дата должна быть в формате ГГГГ-ММ-ДД и не позже сегодняшнего дня.';
        }
    }
    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        $errors['gender'] = 'Выберите пол.';
    }
    if (strlen($biography) > 10000) {
        $errors['biography'] = 'Биография не должна превышать 10000 символов.';
    }
    if (!$contract_accepted) {
        $errors['contract_accepted'] = 'Необходимо согласие на обработку данных.';
    }
    if (empty($interests)) {
        $errors['interests'] = 'Выберите хотя бы один космический интерес.';
    } else {
        foreach ($interests as $int) {
            if (!in_array($int, $allowed_interests)) {
                $errors['interests'] = 'Выбран недопустимый интерес.';
                break;
            }
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Обновление основной таблицы
            $stmt = $pdo->prepare("
                UPDATE application 
                SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, 
                    biography = ?, contract_accepted = ?
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $contract_accepted, $user_id]);

            // Обновление интересов
            $pdo->prepare("DELETE FROM application_interest WHERE application_id = ?")->execute([$user_id]);

            $interest_map = [];
            $stmt = $pdo->query("SELECT id, name FROM interest");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $interest_map[$row['name']] = $row['id'];
            }
            $ins = $pdo->prepare("INSERT INTO application_interest (application_id, interest_id) VALUES (?, ?)");
            foreach ($interests as $int_name) {
                if (isset($interest_map[$int_name])) {
                    $ins->execute([$user_id, $interest_map[$int_name]]);
                }
            }

            $pdo->commit();

            // Обновляем данные в переменных для отображения
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
            $user['email'] = $email;
            $user['birth_date'] = $birth_date;
            $user['gender'] = $gender;
            $user['biography'] = $biography;
            $user['contract_accepted'] = $contract_accepted;
            $user_interests = $interests;

            $message = '<div class="success-message">Профиль успешно обновлён!</div>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="error-message">Ошибка при сохранении: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="error-message">Исправьте ошибки в форме.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль – Космический исследователь</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 100px auto 3rem;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }
        h1 {
            text-align: center;
            color: #42dcff;
            margin-bottom: 0.5rem;
        }
        .profile-subtitle {
            text-align: center;
            color: #ccc;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #ddd;
            margin-bottom: 0.3rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #42dcff;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }
        .checkbox-group input {
            width: 20px;
            height: 20px;
            margin: 0;
        }
        .field-error {
            color: #ff8a80;
            font-size: 0.8rem;
            margin-top: 4px;
            display: block;
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
            width: 100%;
            margin-top: 20px;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,102,255,0.3);
        }
        .success-message, .error-message {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success-message {
            background: rgba(46,125,50,0.2);
            border-left: 4px solid #2e7d32;
            color: #c8e6c9;
        }
        .error-message {
            background: rgba(198,40,40,0.2);
            border-left: 4px solid #c62828;
            color: #ffcdd2;
        }
        .logout-link {
            text-align: center;
            margin-top: 20px;
        }
        .logout-link a {
            color: #ff8a80;
            text-decoration: none;
        }
        @media (max-width: 768px) {
            .profile-container { margin: 80px 20px 2rem; padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Шапка (header) – копия из основного сайта, но без лишних элементов -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <span class="logo-icon">🌌</span>
                    <span class="logo-text">Космический исследователь</span>
                </a>
                <nav class="nav">
                    <ul class="nav-list">
                        <li><a href="index.php" class="nav-link">Главная</a></li>
                        <li><a href="planets.html" class="nav-link">Планеты</a></li>
                        <li><a href="missions.html" class="nav-link">Миссии</a></li>
                        <li><a href="gallery.html" class="nav-link">Галерея</a></li>
                        <li><a href="index.php#calculator" class="nav-link">Калькулятор</a></li>
                        <li><a href="index.php#solar-model" class="nav-link">3D Модель</a></li>
                        <?php if (isset($_SESSION['application_id'])): ?>
                            <li><a href="profile.php" class="nav-link active">👤 Мой профиль</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="nav-link">🔑 Войти</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Открыть меню">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <div class="profile-container">
        <h1>Мой космический профиль</h1>
        <div class="profile-subtitle">Здесь вы можете обновить свои персональные данные</div>

        <?= $message ?>

        <form method="post">
            <div class="form-group">
                <label>ФИО *</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['full_name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Телефон *</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                <?php if (isset($errors['phone'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['phone']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Дата рождения *</label>
                <input type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date']) ?>" required>
                <?php if (isset($errors['birth_date'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['birth_date']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Пол *</label>
                <select name="gender" required>
                    <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
                    <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
                </select>
                <?php if (isset($errors['gender'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['gender']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Космические интересы (выберите несколько) *</label>
                <select name="interests[]" multiple size="6">
                    <?php foreach ($all_interests as $interest): ?>
                        <option value="<?= htmlspecialchars($interest) ?>" <?= in_array($interest, $user_interests) ? 'selected' : '' ?>><?= htmlspecialchars($interest) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['interests'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['interests']) ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Расскажите о себе (почему космос?)</label>
                <textarea name="biography" rows="5"><?= htmlspecialchars($user['biography']) ?></textarea>
                <?php if (isset($errors['biography'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['biography']) ?></span>
                <?php endif; ?>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="contract_accepted" value="1" id="contract" <?= $user['contract_accepted'] ? 'checked' : '' ?>>
                <label for="contract">Я даю согласие на обработку персональных данных *</label>
            </div>
            <?php if (isset($errors['contract_accepted'])): ?>
                <span class="field-error"><?= htmlspecialchars($errors['contract_accepted']) ?></span>
            <?php endif; ?>

            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" class="btn-save">Сохранить изменения</button>
        </form>

        <div class="logout-link">
            <a href="index.php?logout=1">Выйти из аккаунта</a>
        </div>
    </div>

    <!-- Футер (упрощённая версия) -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Космический исследователь</h3>
                    <p>Исследуйте тайны Вселенной вместе с нами</p>
                </div>
                <div class="footer-section">
                    <h4>Разделы</h4>
                    <ul>
                        <li><a href="index.php">Главная</a></li>
                        <li><a href="planets.html">Планеты</a></li>
                        <li><a href="missions.html">Космические миссии</a></li>
                        <li><a href="gallery.html">Галерея</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Космический исследователь. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script src="js/navigation.js"></script>
    <script>
        // Небольшой скрипт для мобильного меню (если нужно)
        const btn = document.getElementById('mobileMenuBtn');
        const nav = document.querySelector('.nav');
        if (btn && nav) {
            btn.addEventListener('click', () => {
                nav.classList.toggle('active');
                btn.classList.toggle('active');
            });
        }
    </script>
</body>
</html>