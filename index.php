<?php
// index.php – единая точка входа
session_start();

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

// Генерация уникального логина (только для первого сохранения)
function generate_unique_login($pdo) {
    do {
        $login = 'cosmo_' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
        $stmt = $pdo->prepare("SELECT id FROM application WHERE login = ?");
        $stmt->execute([$login]);
    } while ($stmt->fetch());
    return $login;
}

function generate_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

// CSRF‑токен
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$is_logged_in = isset($_SESSION['application_id']);
$user_id = $is_logged_in ? $_SESSION['application_id'] : null;

$allowed_interests = [
    'Марс', 'Луна', 'Юпитер', 'Сатурн', 'Чёрные дыры',
    'Экзопланеты', 'Туманности', 'Космические миссии'
];
$allowed_genders = ['male', 'female'];

// ------------- ОБРАБОТКА POST (отправка формы) -------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors_flag = false;

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $biography = trim($_POST['biography'] ?? '');
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $interests = $_POST['interests'] ?? [];

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка CSRF. Обновите страницу.');
    }

    // Валидация (с сохранением в куки для повторного заполнения)
    if (empty($full_name) || !preg_match('/^[а-яА-Яa-zA-Z\s]+$/u', $full_name) || strlen($full_name) > 150) {
        setcookie('full_name_error', '1', time() + 86400);
        $errors_flag = true;
    }
    setcookie('full_name_value', $full_name, time() + 2592000);

    if (empty($phone) || !preg_match('/^[\d\s\-\+\(\)]{6,12}$/', $phone)) {
        setcookie('phone_error', '1', time() + 86400);
        $errors_flag = true;
    }
    setcookie('phone_value', $phone, time() + 2592000);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 86400);
        $errors_flag = true;
    }
    setcookie('email_value', $email, time() + 2592000);

    if (empty($birth_date)) {
        setcookie('birth_date_error', '1', time() + 86400);
        $errors_flag = true;
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date || $date->format('Y-m-d') !== $birth_date || $date > new DateTime('today')) {
            setcookie('birth_date_error', '1', time() + 86400);
            $errors_flag = true;
        }
    }
    setcookie('birth_date_value', $birth_date, time() + 2592000);

    if (empty($gender) || !in_array($gender, $allowed_genders)) {
        setcookie('gender_error', '1', time() + 86400);
        $errors_flag = true;
    }
    setcookie('gender_value', $gender, time() + 2592000);

    if (strlen($biography) > 10000) {
        setcookie('biography_error', '1', time() + 86400);
        $errors_flag = true;
    }
    setcookie('biography_value', $biography, time() + 2592000);

    if (!$contract_accepted) {
        setcookie('contract_accepted_error', '1', time() + 86400);
        $errors_flag = true;
    }
    setcookie('contract_accepted_value', $contract_accepted ? '1' : '0', time() + 2592000);

    if (empty($interests)) {
        setcookie('interests_error', '1', time() + 86400);
        $errors_flag = true;
    } else {
        foreach ($interests as $int) {
            if (!in_array($int, $allowed_interests)) {
                setcookie('interests_error', '1', time() + 86400);
                $errors_flag = true;
                break;
            }
        }
    }
    setcookie('interests_value', implode(',', $interests), time() + 2592000);

    if ($errors_flag) {
        header('Location: index.php');
        exit;
    }

    // Сохранение в БД
    try {
        $pdo = getDB();
        $pdo->beginTransaction();

        if ($is_logged_in) {
            // Обновление данных авторизованного пользователя
            $stmt = $pdo->prepare("
                UPDATE application 
                SET full_name = :full_name, phone = :phone, email = :email,
                    birth_date = :birth_date, gender = :gender, 
                    biography = :biography, contract_accepted = :contract_accepted
                WHERE id = :id
            ");
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':email' => $email,
                ':birth_date' => $birth_date,
                ':gender' => $gender,
                ':biography' => $biography,
                ':contract_accepted' => $contract_accepted,
                ':id' => $user_id
            ]);
            $app_id = $user_id;
            $pdo->prepare("DELETE FROM application_interest WHERE application_id = ?")->execute([$app_id]);
            setcookie('updated', '1', time() + 86400);
        } else {
            // Новая заявка
            $login = generate_unique_login($pdo);
            $plain_pass = generate_password();
            $pass_hash = password_hash($plain_pass, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO application 
                (full_name, phone, email, birth_date, gender, biography, contract_accepted, login, password_hash)
                VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted, :login, :pass_hash)
            ");
            $stmt->execute([
                ':full_name' => $full_name,
                ':phone' => $phone,
                ':email' => $email,
                ':birth_date' => $birth_date,
                ':gender' => $gender,
                ':biography' => $biography,
                ':contract_accepted' => $contract_accepted,
                ':login' => $login,
                ':pass_hash' => $pass_hash
            ]);
            $app_id = $pdo->lastInsertId();
            setcookie('generated_login', $login, time() + 3600);
            setcookie('generated_password', $plain_pass, time() + 3600);
            setcookie('save', '1', time() + 86400);
        }

        // Сохраняем интересы (таблица interest)
        $interest_map = [];
        $stmt = $pdo->query("SELECT id, name FROM interest");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $interest_map[$row['name']] = $row['id'];
        }
        $stmt = $pdo->prepare("INSERT INTO application_interest (application_id, interest_id) VALUES (?, ?)");
        foreach ($interests as $int_name) {
            if (isset($interest_map[$int_name])) {
                $stmt->execute([$app_id, $interest_map[$int_name]]);
            }
        }

        $pdo->commit();

        // Очистка кук ошибок
        foreach (['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'interests'] as $f) {
            setcookie($f . '_error', '', 1);
        }

        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setcookie('db_error', '1', time() + 86400);
        header('Location: index.php');
        exit;
    }
}

// ------------- Обработка GET (выход) -------------
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('PHPSESSID', '', 1);
    header('Location: index.php');
    exit;
}

// ------------- Подготовка переменных для отображения -------------
$messages = [];
$errors = [];
$values = [];

$fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'biography', 'contract_accepted', 'interests'];

foreach ($fields as $field) {
    $errors[$field] = !empty($_COOKIE[$field . '_error']);
}

// Сообщения об ошибках
if ($errors['full_name']) $messages[] = '<div class="error-message">ФИО должно содержать только буквы и пробелы (макс. 150 символов).</div>';
if ($errors['phone']) $messages[] = '<div class="error-message">Телефон должен содержать от 6 до 12 цифр, допускаются символы +, -, (, ), пробел.</div>';
if ($errors['email']) $messages[] = '<div class="error-message">Введите корректный email.</div>';
if ($errors['birth_date']) $messages[] = '<div class="error-message">Дата рождения должна быть в формате ГГГГ-ММ-ДД и не позже сегодняшнего дня.</div>';
if ($errors['gender']) $messages[] = '<div class="error-message">Выберите пол.</div>';
if ($errors['biography']) $messages[] = '<div class="error-message">Биография не должна превышать 10000 символов.</div>';
if ($errors['contract_accepted']) $messages[] = '<div class="error-message">Необходимо подтвердить согласие на обработку данных.</div>';
if ($errors['interests']) $messages[] = '<div class="error-message">Выберите хотя бы один космический интерес.</div>';

foreach ($fields as $field) {
    $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : $_COOKIE[$field . '_value'];
}
if (!empty($_COOKIE['interests_value'])) {
    $values['interests'] = explode(',', $_COOKIE['interests_value']);
} else {
    $values['interests'] = [];
}
$values['contract_accepted'] = !empty($_COOKIE['contract_accepted_value']);

// Если пользователь авторизован и нет ошибок в куках – загружаем его данные из БД
if ($is_logged_in) {
    $has_cookie_errors = false;
    foreach ($fields as $f) {
        if (!empty($_COOKIE[$f . '_error'])) $has_cookie_errors = true;
    }
    if (!$has_cookie_errors) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM application WHERE id = ?");
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $values['full_name'] = $row['full_name'];
            $values['phone'] = $row['phone'];
            $values['email'] = $row['email'];
            $values['birth_date'] = $row['birth_date'];
            $values['gender'] = $row['gender'];
            $values['biography'] = $row['biography'];
            $values['contract_accepted'] = (bool)$row['contract_accepted'];
            // Загружаем интересы из application_interest
            $stmt_int = $pdo->prepare("
                SELECT i.name FROM application_interest ai 
                JOIN interest i ON ai.interest_id = i.id 
                WHERE ai.application_id = ?
            ");
            $stmt_int->execute([$user_id]);
            $values['interests'] = $stmt_int->fetchAll(PDO::FETCH_COLUMN);
        }
    }
}

// Сообщения об успешном сохранении / обновлении
if (!empty($_COOKIE['save'])) {
    setcookie('save', '', 1);
    $messages[] = '<div class="success-message">Данные успешно сохранены!</div>';
}
if (!empty($_COOKIE['updated'])) {
    setcookie('updated', '', 1);
    $messages[] = '<div class="success-message">Данные успешно обновлены!</div>';
}
// Показ сгенерированных логина и пароля
if (!empty($_COOKIE['generated_login']) && !empty($_COOKIE['generated_password'])) {
    $gen_login = $_COOKIE['generated_login'];
    $gen_pass = $_COOKIE['generated_password'];
    setcookie('generated_login', '', 1);
    setcookie('generated_password', '', 1);
    $messages[] = '<div class="success-message credentials">
        <strong>Заявка успешно отправлена!</strong><br>
        Ваш логин: <strong>' . htmlspecialchars($gen_login) . '</strong><br>
        Ваш пароль: <strong>' . htmlspecialchars($gen_pass) . '</strong><br>
        <small>Сохраните их для входа в личный кабинет.</small>
    </div>';
}

// Список интересов из БД (таблица interest)
$pdo = getDB();
$interests_from_db = [];
$stmt = $pdo->query("SELECT name FROM interest ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $interests_from_db[] = $row['name'];
}
if (empty($interests_from_db)) {
    $interests_from_db = $allowed_interests;
}

// Подключаем шаблон страницы (все HTML, CSS, JS)
require 'page.php';