<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Космические миссии | Космический исследователь</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/missions.css">
</head>
<body>
    
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
                        <li><a href="planets.php" class="nav-link">Планеты</a></li>
                        <li><a href="missions.php" class="nav-link active">Миссии</a></li>
                        <li><a href="index.php#gallery" class="nav-link">Галерея</a></li>
                        
                        
                        <li class="nav-item dropdown">
                            <button class="nav-link dropdown-toggle">
                                Разное
                                <span class="dropdown-arrow">▼</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="index.php#calculator" class="dropdown-link">
                                        <span class="dropdown-icon">⚖️</span>
                                        Калькулятор веса на планетах
                                    </a>
                                </li>
                              
                                <li>
                                    <a href="index.php#solar-model" class="dropdown-link">
                                        <span class="dropdown-icon">🌐</span>
                                        3D Модель Солнечной системы
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
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

    
    <main class="missions-page">
        <div class="container">
           
            <div class="page-header">
                <h1 class="page-title">
                    <span class="title-icon">🚀</span>
                    Космические миссии
                </h1>
                <p class="page-subtitle">
                    Исследуйте исторические и современные космические экспедиции, которые расширили границы нашего познания Вселенной
                </p>
                <div class="page-stats">
                    <div class="stat">
                        <span class="stat-number">197</span>
                        <span class="stat-label">успешных миссий</span>
                    </div>
                    <div class="stat">
                        <span class="stat-number">60+</span>
                        <span class="stat-label">лет исследований</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon">🌍</span>
                        <span class="stat-label">12 стран участвуют</span>
                    </div>
                </div>
            </div>

            
            <div class="mission-filters">
                <div class="filters-header">
                    <h3>Фильтр по типу миссий:</h3>
                </div>
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">Все миссии</button>
                    <button class="filter-btn" data-filter="planetary">Планетарные</button>
                    <button class="filter-btn" data-filter="orbital">Орбитальные</button>
                    <button class="filter-btn" data-filter="manned">Пилотируемые</button>
                    <button class="filter-btn" data-filter="telescope">Телескопы</button>
                </div>
            </div>

            
            <div class="missions-grid" id="missionsGrid">
                
            </div>

            
            <div class="info-section">
                <div class="info-card">
                    <div class="info-icon">📡</div>
                    <h3>Научные цели</h3>
                    <p>Космические миссии проводятся для изучения планет, астероидов, комет, Солнца, поиска жизни и тестирования новых технологий.</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">💰</div>
                    <h3>Стоимость и финансирование</h3>
                    <p>Миссии финансируются государственными космическими агентствами (NASA, ESA, Роскосмос) и частными компаниями.</p>
                </div>
                <div class="info-card">
                    <div class="info-icon">🔭</div>
                    <h3>Будущие миссии</h3>
                    <p>Планируются миссии к Луне, Марсу, Венере, а также поиск экзопланет и изучение тёмной материи.</p>
                </div>
            </div>
        </div>
    </main>

    
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
                        <li><a href="planets.php">Планеты</a></li>
                        <li><a href="missions.php">Космические миссии</a></li>
                        <li><a href="gallery.php">Галерея</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Инструменты</h4>
                    <ul>
                        <li><a href="index.php#calculator">Калькулятор веса</a></li>
                        <li><a href="index.php#solar-model">3D Модель</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Космический исследователь.</p>
               
            </div>
        </div>
    </footer>

    
    <script src="js/script.js"></script>
    <script src="js/navigation.js"></script>
    <script src="js/missions.js"></script>
</body>
</html>