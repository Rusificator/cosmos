<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Космический исследователь - Исследуйте Вселенную</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/feedbackModal.css">
    <link rel="stylesheet" href="css/footerForm.css">
    <link rel="stylesheet" href="css/weight-calculator.css">
    <link rel="stylesheet" href="css/solar-model.css">
    <style>
        .anketa-section {
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 2rem;
            margin: 3rem 0;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .anketa-section h2 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(45deg, #42dcff, #bd34fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .field-error {
            color: #ff8a80;
            font-size: 0.8rem;
            margin-top: 4px;
            display: block;
        }
        .credentials {
            background: #1a1a2e;
            border: 2px solid #ffcc00;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
        }
        .success-message {
            background: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .error-message {
            background: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .anketa-section .form-group {
            margin-bottom: 1rem;
        }
        .anketa-section label {
            display: block;
            margin-bottom: 0.3rem;
            color: #e0e0e0;
        }
        .anketa-section input, .anketa-section select, .anketa-section textarea {
            width: 100%;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            color: white;
        }
        .anketa-section .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .anketa-section .checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .anketa-section button {
            background: linear-gradient(45deg, #0066ff, #00ccff);
            border: none;
            padding: 12px 24px;
            border-radius: 30px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .anketa-section button:hover {
            transform: translateY(-2px);
        }
        .logged-in-badge {
            background: #2e7d32;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="#" class="logo">
                    <span class="logo-icon">🌌</span>
                    <span class="logo-text">Космический исследователь</span>
                </a>
                
                <nav class="nav">
                    <ul class="nav-list">
                        <li><a href="#" class="nav-link active">Главная</a></li>
                       <li><a href="planets.html" class="nav-link">Планеты</a></li>
                        <li><a href="missions.html" class="nav-link">Миссии</a></li>
                        <li><a href="#gallery" class="nav-link">Галерея</a></li>
                        
                        
                        <li class="nav-item dropdown">
                            <button class="nav-link dropdown-toggle">
                                Разное
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#calculator" class="dropdown-link">
                                        <span class="dropdown-icon">⚖️</span>
                                        Калькулятор веса на планетах
                                    </a>
                                </li>
                            
                                <li>
                                    <a href="#solar-model" class="dropdown-link">
                                        <span class="dropdown-icon">🌐</span>
                                        3D Модель Солнечной системы
                                    </a>
                                </li>
                            </ul>
                        </li>
                         <?php if (isset($_SESSION['application_id'])): ?>
                            <li>
                                <a href="profile.php" class="contact-btn"> Профиль</a>
                            </li>
                            
                        <?php else: ?>
                            <li>
                                <a href="login.php" class="contact-btn"> Войти</a>
                            </li>
                        <?php endif; ?>
                        
                    </ul>
                </nav>
                
                
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Открыть меню">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    
<div class="modal-backdrop" id="modalBackdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Обратная связь</h2>
            <button 
                type="button" 
                class="close-btn"
                id="modalCloseBtn"
                aria-label="Закрыть"
            >
                ×
            </button>
        </div>

        <div class="modal-body">
            <form id="feedbackForm" class="feedback-form">
                <div class="form-group">
                    <label for="fullName" class="form-label">ФИО *</label>
                    <input
                        type="text"
                        class="form-control"
                        id="fullName"
                        name="fullName"
                        required
                        placeholder="Иванов Иван Иванович"
                    >
                    <div class="error-message" id="fullNameError"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email *</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        required
                        placeholder="example@mail.com"
                    >
                    <div class="error-message" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label for="message" class="form-label">Сообщение *</label>
                    <textarea
                        class="form-control"
                        id="message"
                        name="message"
                        rows="3"
                        required
                        placeholder="Введите ваше сообщение..."
                    ></textarea>
                    <div class="error-message" id="messageError"></div>
                </div>

                <div class="form-group checkbox-group">
                    <input
                        type="checkbox"
                        class="form-checkbox"
                        id="agree"
                        name="agree"
                        required
                    >
                    <label for="agree" class="checkbox-label">
                        <span class="checkbox-text">Согласен с политикой обработки персональных данных *</span>
                    </label>
                    <div class="error-message" id="agreeError"></div>
                </div>

                
                <div id="formMessage" class="message" style="display: none;"></div>

                <div class="form-buttons">
                    <button 
                        type="button" 
                        class="clear-btn"
                        id="clearFormBtn"
                    >
                        Очистить
                    </button>
                    <button 
                        type="submit" 
                        class="submit-btn"
                        id="submitBtn"
                    >
                        <span id="submitText">Отправить</span>
                        <span id="loadingSpinner" class="loading-spinner" style="display: none;"></span>
                    </button>
                </div>

                
                <input type="hidden" name="_gotcha" style="display:none !important">
                <input type="hidden" name="_subject" value="Новое сообщение с сайта Космический исследователь">
                <input type="hidden" name="_language" value="ru">
            </form>
        </div>
    </div>
</div>

    
    <div class="home-page">
        
        <div class="video-background">
            <video 
                autoplay 
                muted 
                loop 
                class="background-video"
                playsinline
            >
                <source src="content/video/Solar-System.mp4" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
            <div class="video-overlay"></div>
        </div>
        
        
        <div class="hero-content">
            <div class="container">
                <h1 class="hero-title">
                    Откройте <span class="highlight">тайны Вселенной</span>
                </h1>
                <p class="hero-subtitle">
                    Отправьтесь в незабываемое путешествие по Солнечной системе 
                    и за её пределы
                </p>
                <button class="cta-button" id="startJourneyBtn">
                    <span class="cta-icon">🚀</span>
                    Отправиться в путешествие
                </button>
            </div>
        </div>

        
        <section class="features-section">
            <div class="container">
                <h2>Что вас ждет?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🪐</div>
                        <h3>Планеты Солнечной системы</h3>
                        <p>Изучите все 8 планет с уникальными характеристиками и фотографиями</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⭐</div>
                        <h3>Интересные факты</h3>
                        <p>Узнайте удивительные научные открытия и космические явления</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔭</div>
                        <h3>Космические миссии</h3>
                        <p>Исследуйте исторические и современные космические экспедиции</p>
                    </div>
                </div>
            </div>
        </section>

        
        <section class="timeline-section" id="timelineSection">
            <div class="container">
                <div class="timeline-header">
                    <h2>Космический прогресс</h2>
                    <p class="timeline-subtitle">
                        Ключевые вехи в освоении космоса: от первых спутников до межзвездных миссий
                    </p>
                </div>
                
                <div class="timeline-container">
                    <div class="timeline-line"></div>
                    
                </div>
                
                <div class="timeline-note">
                    <p>История продолжается: новые миссии планируются каждый год</p>
                </div>
            </div>
        </section>

        
        <section class="statistics-section" id="statisticsSection">
            <div class="container">
                <div class="statistics-header">
                    <h2>Космос в цифрах</h2>
                    <p class="statistics-subtitle">
                        Удивительные факты о нашей Вселенной, которые заставят вас задуматься
                    </p>
                </div>
                
                <div class="statistics-grid">
                    <div class="stat-item">
                        <div class="stat-line"></div>
                        <div class="stat-icon">🪐</div>
                        <div class="stat-number-container">
                            <span class="stat-number" data-target="8">0</span>
                        </div>
                        <div class="stat-label">
                            <span class="stat-label-main">планет</span>
                            <span class="stat-label-sub">в Солнечной системе</span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-line"></div>
                        <div class="stat-icon">🌕</div>
                        <div class="stat-number-container">
                            <span class="stat-number" data-target="200">0</span>
                        </div>
                        <div class="stat-label">
                            <span class="stat-label-main">спутников</span>
                            <span class="stat-label-sub">вращается вокруг планет</span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-line"></div>
                        <div class="stat-icon">☄️</div>
                        <div class="stat-number-container">
                            <span class="stat-number" data-target="500000">0</span>
                            <span class="stat-plus">+</span>
                        </div>
                        <div class="stat-label">
                            <span class="stat-label-main">космических объектов</span>
                            <span class="stat-label-sub">отслеживается NASA</span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-line"></div>
                        <div class="stat-icon">🚀</div>
                        <div class="stat-number-container">
                            <span class="stat-number" data-target="197">0</span>
                        </div>
                        <div class="stat-label">
                            <span class="stat-label-main">космических миссий</span>
                            <span class="stat-label-sub">успешно выполнено</span>
                        </div>
                    </div>
                </div>
                
                <div class="statistics-note">
                    <p>Все числа основаны на актуальных научных данных и обновляются по мере получения новой информации</p>
                </div>
            </div>
        </section>

        
        <section class="auto-gallery-section" id="gallery">
            <div class="container">
                <div class="gallery-header">
                    <h2>Космос в объективе</h2>
                    <p class="gallery-subtitle">
                        Самые впечатляющие фотографии космоса, сделанные телескопами и космическими аппаратами
                    </p>
                </div>
                
                <div class="gallery-container">
                    <div class="gallery-track" id="galleryTrack">
                        
                    </div>
                </div>

                <div class="gallery-controls">
                    <div class="control-info">
                        <span class="control-icon">🌍</span>
                        <span>Впечатляющие изображения</span>
                    </div>
                    <button class="view-gallery-btn" id="viewGalleryBtn" onclick="window.location.href='gallery.html'">
                        <span class="btn-icon">📷</span>
                        Смотреть всю галерею
                    </button>
            </div>
        </section>








        
<section class="solar-model-section" id="solar-model">
    <div class="container">
        <div class="model-header">
            <h2 class="model-title">
                <span class="title-icon">🪐</span>
                Интерактивная модель Солнечной системы
            </h2>
            <p class="model-subtitle">
                Исследуйте планеты, их орбиты и ночное небо в реальном времени с помощью 
                модели <strong>Solar System Scope</strong>. Меняйте масштаб, включайте движение планет и изучайте космические объекты.
            </p>
            <div class="model-requirements">
                <p>⚠️ <strong>Для работы модели требуется:</strong> браузер на компьютере (Chrome, Firefox, Edge) с поддержкой <strong>WebGL</strong>. На мобильных устройствах модель может не работать.</p>
            </div>
        </div>

        <div class="model-container">
            
            <iframe
                src="https://www.solarsystemscope.com/iframe"
                title="Интерактивная 3D модель Солнечной системы"
                class="solar-iframe"
                width="100%"
                height="700"
                frameborder="0"
                scrolling="no"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer"
            ></iframe>
        </div>

        <div class="model-instructions">
            <h3>💡 Как пользоваться?</h3>
            <ul>
                <li><strong>Панель управления справа:</strong> выбор языка, масштаба, включение/выключение звука.</li>
                <li><strong>Панель слева:</strong> выбор точки обзора (например, с Земли или Солнца) и настройки отображения орбит.</li>
                <li><strong>Панель внизу:</strong> управление временем — запуск движения планет с разной скоростью.</li>
                <li>Используйте <strong>колесико мыши</strong> для приближения и <strong>перетаскивание</strong> для вращения обзора.</li>
            </ul>
            <p class="model-note">Особенность: модель отображает положение планет на основе актуальных данных NASA.</p>
        </div>
    </div>
</section>


















<section class="weight-calculator-section" id="calculator">
    <div class="container">
        <div class="calculator-content">
            <div class="calculator-header">
                <h2 class="page-title">Калькулятор веса на планетах</h2>
                <p class="page-subtitle">
                    Узнайте, сколько бы вы весили на других планетах Солнечной системы
                </p>
            </div>

            
            <div class="input-panel">
                <div class="weight-input-group">
                    <label for="earth-weight" class="input-label">
                        Ваш вес на Земле:
                    </label>
                    <div class="input-with-unit">
                        <input
                            type="number"
                            id="earth-weight"
                            class="weight-input"
                            placeholder="Введите ваш вес"
                            min="0"
                            step="0.1"
                        />
                        <select class="unit-select" id="unit-select">
                            <option value="kg">кг</option>
                            <option value="lb">фунт</option>
                        </select>
                    </div>
                </div>
                
                <div class="current-weight" id="current-weight-display">
                    <span>Ваш вес: </span>
                    <strong id="current-weight-value">0 кг</strong>
                </div>
            </div>

            
            <div class="results-section" id="results-section" style="display: none;">
                <h3 class="results-title">Ваш вес на других небесных телах:</h3>
                
                <div class="planets-grid" id="planets-grid">
                    
                </div>
            </div>

            
            <div class="empty-state" id="empty-state">
                <div class="empty-icon">⚖️</div>
                <h3>Введите ваш вес</h3>
                <p>Узнайте, как гравитация других планет повлияет на ваш вес</p>
                <div class="fun-facts">
                    <div class="fun-fact">
                        <span>🪐</span>
                        На Юпитере ваш вес в 2.3 раза больше!
                    </div>
                    <div class="fun-fact">
                        <span>🌙</span>
                        На Луне ваш вес уменьшится в 6 раз!
                    </div>
                    <div class="fun-fact">
                        <span>🪐</span>
                        На Плутоне вы почти невесомы!
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>













    </div>

   
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
                    <li><a href="#">Главная</a></li>
                    <li><a href="planets.html">Планеты</a></li>
                    <li><a href="missions.html">Космические миссии</a></li>
                    <li><a href="#gallery">Галерея</a></li>
                </ul>
            </div>



        </div>





        <div class="footer-content">
             <div class="anketa-section" id="anketa-section">
                <h2>⚡ Гонка интересов</h2>
                <?php if ($is_logged_in): ?>
                    <div class="logged-in-badge">
                        ✅ Вы авторизованы (логин: <?= htmlspecialchars($_SESSION['user_login'] ?? '') ?>)
                        <a href="?logout=1" style="color:#ffcc00; margin-left:15px;">Выйти</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($messages)): ?>
                    <div class="messages">
                        <?php foreach ($messages as $msg): ?>
                            <?= $msg ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <div class="form-group">
                        <label for="full_name">ФИО *</label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($values['full_name'] ?? '') ?>"
                            <?= !empty($errors['full_name']) ? 'class="error"' : '' ?>>
                        <?php if (!empty($errors['full_name'])): ?>
                            <span class="field-error">Некорректное ФИО</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Телефон *</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                            <?= !empty($errors['phone']) ? 'class="error"' : '' ?>>
                        <?php if (!empty($errors['phone'])): ?>
                            <span class="field-error">Некорректный телефон</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                            <?= !empty($errors['email']) ? 'class="error"' : '' ?>>
                        <?php if (!empty($errors['email'])): ?>
                            <span class="field-error">Некорректный email</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Дата рождения *</label>
                        <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>"
                            <?= !empty($errors['birth_date']) ? 'class="error"' : '' ?>>
                        <?php if (!empty($errors['birth_date'])): ?>
                            <span class="field-error">Некорректная дата</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Пол *</label>
                        <div class="radio-group">
                            <label><input type="radio" name="gender" value="male" <?= ($values['gender'] ?? '') === 'male' ? 'checked' : '' ?>> Мужской</label>
                            <label><input type="radio" name="gender" value="female" <?= ($values['gender'] ?? '') === 'female' ? 'checked' : '' ?>> Женский</label>
                        </div>
                        <?php if (!empty($errors['gender'])): ?>
                            <span class="field-error">Выберите пол</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="interests">Космические интересы (выберите несколько) *</label>
                        <select id="interests" name="interests[]" multiple size="6"
                            <?= !empty($errors['interests']) ? 'class="error"' : '' ?>>
                            <?php foreach ($interests_from_db as $interest): ?>
                                <option value="<?= htmlspecialchars($interest) ?>"
                                    <?= in_array($interest, $values['interests'] ?? []) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($interest) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['interests'])): ?>
                            <span class="field-error">Выберите хотя бы один интерес</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="biography">Расскажите о себе (почему космос?)</label>
                        <textarea id="biography" name="biography" rows="5"
                            <?= !empty($errors['biography']) ? 'class="error"' : '' ?>><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
                        <?php if (!empty($errors['biography'])): ?>
                            <span class="field-error">Биография не должна превышать 10000 символов</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group checkbox">
                        <label>
                            <input type="checkbox" name="contract_accepted" value="1"
                                <?= !empty($values['contract_accepted']) ? 'checked' : '' ?>
                                <?= !empty($errors['contract_accepted']) ? 'class="error"' : '' ?>>
                            Я даю согласие на обработку персональных данных *
                        </label>
                        <?php if (!empty($errors['contract_accepted'])): ?>
                            <span class="field-error">Необходимо подтвердить согласие</span>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit"><?= $is_logged_in ? 'Обновить данные' : 'Отправить заявку' ?></button>
                </form>

                <?php if (!$is_logged_in): ?>
                    <div style="margin-top: 20px; text-align: center;">
                        <small>Уже отправляли заявку? <a href="login.php" style="color:#42dcff;">Войти</a></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <div class="footer-bottom">
                <p>&copy; 2026 Космический исследователь. Все права защищены.</p>
        </div>

        
    </div>
</footer>

    
    <script src="js/script.js"></script>
    <script src="js/navigation.js"></script>
    <script src="js/feedbackForm.js"></script>
    <script src="js/footerForm.js"></script>
    <script src="js/weight-calculator.js"></script>
   <script src="js/solar-model.js"></script>

















   <!-- Модальное окно для логина/пароля -->
<div id="credentialsModal" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-credentials">
        <div class="modal-header">
            <h3>🎉 Регистрация успешна!</h3>
        </div>
        <div class="modal-body">
            <p>Ваши данные для входа (сохраните их!):</p>
            <div class="credentials-box">
                <strong>Логин:</strong> <span id="modalLogin"></span><br>
                <strong>Пароль:</strong> <span id="modalPassword"></span>
            </div>
            <p>Вы будете автоматически авторизованы после закрытия окна.</p>
            <button id="closeCredentialsModal" class="btn-confirm">Я сохранил(а) логин и пароль</button>
        </div>
    </div>
</div>
<?php if (isset($_SESSION['show_credentials_modal']) && $_SESSION['show_credentials_modal']): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('credentialsModal');
        const loginSpan = document.getElementById('modalLogin');
        const passwordSpan = document.getElementById('modalPassword');
        
        loginSpan.textContent = '<?= htmlspecialchars($_SESSION['temp_login']) ?>';
        passwordSpan.textContent = '<?= htmlspecialchars($_SESSION['temp_password']) ?>';
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // блокируем скролл
        
        const closeBtn = document.getElementById('closeCredentialsModal');
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            // Отправляем AJAX-запрос для очистки сессии
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'clear_credentials_modal=1'
            });
        });
    });
</script>
<?php endif; ?>
</body>
</html>