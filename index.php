<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="">
    <title>VolvoCarsBy — Официальный дилер</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <header class="header" id="header">
        <div class="container">
            <nav class="nav">
                <a href="/" class="nav-logo">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="3" />
                        <line x1="50" y1="5" x2="50" y2="95" stroke="currentColor" stroke-width="3" />
                        <line x1="5" y1="50" x2="95" y2="50" stroke="currentColor" stroke-width="3" />
                        <line x1="17" y1="17" x2="83" y2="83" stroke="currentColor" stroke-width="2" />
                    </svg>
                    Volvo
                </a>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="#models">Модели</a></li>
                    <li><a href="#electric">Электрификация</a></li>
                    <li><a href="#safety">Безопасность</a></li>
                    <li><a href="#services">Сервис</a></li>
                    <li><a href="#contact">Контакты</a></li>
                </ul>
                <div class="nav-actions">
                    <a href="#test-drive">Тест-драйв</a>
                    <a href="#configurator" class="btn btn-primary" style="padding: 10px 24px; font-size: 12px;">Конфигуратор</a>
                </div>
                <button class="mobile-toggle" id="mobileToggle" aria-label="Меню">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="hero-badge">Новый 2026</span>
            <h1>Создан для <strong>вашей жизни</strong></h1>
            <p>Откройте линейку автомобилей Volvo, где скандинавский дизайн сочетается с передовыми технологиями безопасности и электрической мощностью.</p>
            <div class="hero-buttons">
                <a href="#models" class="btn btn-primary">Исследовать модели</a>
                <a href="#test-drive" class="btn btn-outline">Записаться на тест-драйв</a>
            </div>
        </div>
        <div class="hero-scroll">
            <span>Прокрутите вниз</span>
            <div class="arrow"></div>
        </div>
    </section>

    <section class="section" id="models">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Линейка моделей</span>
                <h2 class="section-title">Найдите свой <strong>Volvo</strong></h2>
                <p class="section-subtitle">Каждый автомобиль Volvo создан с заботой о людях и окружающей среде. Выберите модель, которая подходит именно вам.</p>
            </div>
            <div class="models-grid">

                <div class="model-card">
                    <div class="model-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1619767886558-efdc259cde1a?w=600&q=80" alt="Volvo EX90" class="model-image">
                        <span class="model-tag">Полностью электрический</span>
                    </div>
                    <div class="model-info">
                        <h3 class="model-name">EX90</h3>
                        <p class="model-type">Полноразмерный SUV</p>
                        <div class="model-specs">
                            <div class="spec">
                                <span class="spec-value">600 км</span>
                                <span class="spec-label">Запас хода</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">4.9 с</span>
                                <span class="spec-label">0-100 км/ч</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">7</span>
                                <span class="spec-label">Мест</span>
                            </div>
                        </div>
                        <p class="model-price">от <span>₽ 9 490 000</span></p>
                        <div class="model-actions">
                            <a href="#" class="btn btn-dark">Подробнее</a>
                            <a href="#" class="btn btn-outline-dark">Конфигуратор</a>
                        </div>
                    </div>
                </div>

                <div class="model-card">
                    <div class="model-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=600&q=80" alt="Volvo XC90" class="model-image">
                    </div>
                    <div class="model-info">
                        <h3 class="model-name">XC90</h3>
                        <p class="model-type">Флагманский SUV</p>
                        <div class="model-specs">
                            <div class="spec">
                                <span class="spec-value">300 л.с.</span>
                                <span class="spec-label">Мощность</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">6.5 с</span>
                                <span class="spec-label">0-100 км/ч</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">7</span>
                                <span class="spec-label">Мест</span>
                            </div>
                        </div>
                        <p class="model-price">от <span>₽ 7 890 000</span></p>
                        <div class="model-actions">
                            <a href="#" class="btn btn-dark">Подробнее</a>
                            <a href="#" class="btn btn-outline-dark">Конфигуратор</a>
                        </div>
                    </div>
                </div>

                <div class="model-card">
                    <div class="model-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=600&q=80" alt="Volvo S90" class="model-image">
                    </div>
                    <div class="model-info">
                        <h3 class="model-name">S90</h3>
                        <p class="model-type">Бизнес-седан</p>
                        <div class="model-specs">
                            <div class="spec">
                                <span class="spec-value">250 л.с.</span>
                                <span class="spec-label">Мощность</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">7.0 с</span>
                                <span class="spec-label">0-100 км/ч</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">5</span>
                                <span class="spec-label">Мест</span>
                            </div>
                        </div>
                        <p class="model-price">от <span>₽ 5 490 000</span></p>
                        <div class="model-actions">
                            <a href="#" class="btn btn-dark">Подробнее</a>
                            <a href="#" class="btn btn-outline-dark">Конфигуратор</a>
                        </div>
                    </div>
                </div>

                <div class="model-card">
                    <div class="model-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=600&q=80" alt="Volvo XC60" class="model-image">
                        <span class="model-tag">Бестселлер</span>
                    </div>
                    <div class="model-info">
                        <h3 class="model-name">XC60</h3>
                        <p class="model-type">Среднеразмерный SUV</p>
                        <div class="model-specs">
                            <div class="spec">
                                <span class="spec-value">250 л.с.</span>
                                <span class="spec-label">Мощность</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">6.8 с</span>
                                <span class="spec-label">0-100 км/ч</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">5</span>
                                <span class="spec-label">Мест</span>
                            </div>
                        </div>
                        <p class="model-price">от <span>₽ 5 190 000</span></p>
                        <div class="model-actions">
                            <a href="#" class="btn btn-dark">Подробнее</a>
                            <a href="#" class="btn btn-outline-dark">Конфигуратор</a>
                        </div>
                    </div>
                </div>

                <div class="model-card">
                    <div class="model-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1583121274602-3e2820c69888?w=600&q=80" alt="Volvo V60" class="model-image">
                    </div>
                    <div class="model-info">
                        <h3 class="model-name">V60 Cross Country</h3>
                        <p class="model-type">Универсал повышенной проходимости</p>
                        <div class="model-specs">
                            <div class="spec">
                                <span class="spec-value">250 л.с.</span>
                                <span class="spec-label">Мощность</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">6.9 с</span>
                                <span class="spec-label">0-100 км/ч</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">5</span>
                                <span class="spec-label">Мест</span>
                            </div>
                        </div>
                        <p class="model-price">от <span>₽ 4 690 000</span></p>
                        <div class="model-actions">
                            <a href="#" class="btn btn-dark">Подробнее</a>
                            <a href="#" class="btn btn-outline-dark">Конфигуратор</a>
                        </div>
                    </div>
                </div>

                <div class="model-card">
                    <div class="model-image-wrapper">
                        <img src="https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=600&q=80" alt="Volvo EX30" class="model-image">
                        <span class="model-tag">Новинка</span>
                    </div>
                    <div class="model-info">
                        <h3 class="model-name">EX30</h3>
                        <p class="model-type">Компактный электрический SUV</p>
                        <div class="model-specs">
                            <div class="spec">
                                <span class="spec-value">480 км</span>
                                <span class="spec-label">Запас хода</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">5.3 с</span>
                                <span class="spec-label">0-100 км/ч</span>
                            </div>
                            <div class="spec">
                                <span class="spec-value">5</span>
                                <span class="spec-label">Мест</span>
                            </div>
                        </div>
                        <p class="model-price">от <span>₽ 3 990 000</span></p>
                        <div class="model-actions">
                            <a href="#" class="btn btn-dark">Подробнее</a>
                            <a href="#" class="btn btn-outline-dark">Конфигуратор</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="split-section" id="electric">
        <div class="split-image">
            <img src="https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=960&q=80" alt="Volvo Electric">
        </div>
        <div class="split-content">
            <span class="section-label">Электрификация</span>
            <h2 class="section-title">Полностью электрическое <strong>будущее</strong></h2>
            <p>Volvo стремится к полному переходу на электромобили к 2030 году. Наши полностью электрические модели предлагают нулевой уровень выбросов, мгновенный крутящий момент и бесшумное вождение — без компромиссов в области безопасности и комфорта.</p>
            <div>
                <a href="#" class="btn btn-dark">Узнать больше об электромобилях</a>
            </div>
        </div>
    </section>

    <section class="section bg-dark" id="safety">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Наследие Volvo</span>
                <h2 class="section-title">Безопасность — наша <strong>основа</strong></h2>
                <p class="section-subtitle">С 1927 года мы ставим людей в центр всего, что делаем. Безопасность — это не просто функция, это философия Volvo.</p>
            </div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3>IntelliSafe</h3>
                    <p>Комплексная система безопасности, включающая автоматическое экстренное торможение, удержание в полосе и мониторинг слепых зон.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 6v6l4 2" />
                        </svg>
                    </div>
                    <h3>Pilot Assist</h3>
                    <p>Полуавтономное вождение, помогающее поддерживать скорость, дистанцию и положение на дороге до 130 км/ч.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </div>
                    <h3>City Safety</h3>
                    <p>Система распознаёт пешеходов, велосипедистов и крупных животных, автоматически применяя торможение при необходимости.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                            <line x1="8" y1="21" x2="16" y2="21" />
                            <line x1="12" y1="17" x2="12" y2="21" />
                        </svg>
                    </div>
                    <h3>Google Built-in</h3>
                    <p>Встроенные сервисы Google Maps, Google Assistant и Play Store для интуитивного управления автомобилем.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="banner" id="test-drive">
        <div class="banner-content">
            <h2>Запишитесь на <strong>тест-драйв</strong></h2>
            <p>Почувствуйте скандинавскую роскошь и мощность Volvo лично. Запишитесь на бесплатный тест-драйв в нашем автосалоне.</p>
            <a href="#" class="btn btn-primary">Записаться сейчас</a>
        </div>
    </section>

    <section class="section bg-light" id="services">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Владельцам Volvo</span>
                <h2 class="section-title">Наши <strong>услуги</strong></h2>
                <p class="section-subtitle">Мы обеспечиваем полный цикл обслуживания вашего автомобиля Volvo — от покупки до сервисной поддержки.</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <h3>Сервисное обслуживание</h3>
                    <p>Оригинальные запчасти и сертифицированные специалисты Volvo. Плановое ТО, диагностика и ремонт любой сложности.</p>
                    <a href="#">Записаться на сервис →</a>
                </div>
                <div class="service-card">
                    <h3>Trade-In</h3>
                    <p>Обменяйте ваш текущий автомобиль на новый Volvo с выгодой. Бесплатная оценка и быстрое оформление сделки.</p>
                    <a href="#">Оценить автомобиль →</a>
                </div>
                <div class="service-card">
                    <h3>Кредитование и лизинг</h3>
                    <p>Гибкие финансовые программы от ведущих банков-партнёров. Одобрение за 15 минут, ставки от 4.9% годовых.</p>
                    <a href="#">Рассчитать платёж →</a>
                </div>
                <div class="service-card">
                    <h3>Страхование</h3>
                    <p>КАСКО и ОСАГО на специальных условиях для владельцев Volvo. Быстрое урегулирование страховых случаев.</p>
                    <a href="#">Узнать условия →</a>
                </div>
                <div class="service-card">
                    <h3>Volvo On Call</h3>
                    <p>Удалённое управление автомобилем через приложение: климат-контроль, блокировка дверей, отслеживание местоположения.</p>
                    <a href="#">Подробнее →</a>
                </div>
                <div class="service-card">
                    <h3>Доставка автомобиля</h3>
                    <p>Доставим ваш новый Volvo прямо к двери. Доступно по всей России с полным пакетом документов.</p>
                    <a href="#">Оформить доставку →</a>
                </div>
            </div>
        </div>
    </section>

    <section class="split-section">
        <div class="split-content" style="background: var(--volvo-blue);">
            <span class="section-label" style="color: rgba(255,255,255,0.7);">Конфигуратор</span>
            <h2 class="section-title" style="color: var(--volvo-white);">Создайте свой <strong>идеальный Volvo</strong></h2>
            <p style="color: rgba(255,255,255,0.8);">Выберите модель, цвет кузова, отделку салона, колёсные диски и дополнительные опции. Соберите автомобиль мечты в нашем онлайн-конфигураторе.</p>
            <div>
                <a href="#" class="btn btn-primary">Открыть конфигуратор</a>
            </div>
        </div>
        <div class="split-image">
            <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=960&q=80" alt="Volvo Configurator">
        </div>
    </section>

    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3>Volvo</h3>
                    <p>Официальный дилерский центр Volvo. Скандинавская роскошь, безопасность и инновации с 1927 года.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Telegram">TG</a>
                        <a href="#" aria-label="VKontakte">VK</a>
                        <a href="#" aria-label="YouTube">YT</a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Модели</h4>
                    <ul>
                        <li><a href="#">EX90</a></li>
                        <li><a href="#">EX30</a></li>
                        <li><a href="#">XC90</a></li>
                        <li><a href="#">XC60</a></li>
                        <li><a href="#">S90</a></li>
                        <li><a href="#">V60</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Покупателям</h4>
                    <ul>
                        <li><a href="#">Конфигуратор</a></li>
                        <li><a href="#">Тест-драйв</a></li>
                        <li><a href="#">Trade-In</a></li>
                        <li><a href="#">Кредитование</a></li>
                        <li><a href="#">Лизинг</a></li>
                        <li><a href="#">Страхование</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Контакты</h4>
                    <ul>
                        <li><a href="tel:+78001234567">8 (800) 123-45-67</a></li>
                        <li><a href="mailto:info@volvo-showroom.ru">info@volvo-showroom.ru</a></li>
                        <li><a href="#">г. Москва, ул. Примерная, 1</a></li>
                        <li><a href="#">Пн-Вс: 9:00 — 21:00</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Volvo Car Showroom. Все права защищены.</p>
                <div class="footer-bottom-links">
                    <a href="#">Политика конфиденциальности</a>
                    <a href="#">Условия использования</a>
                    <a href="#">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        const mobileToggle = document.getElementById('mobileToggle');
        const navMenu = document.getElementById('navMenu');
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });

        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
            });
        });
    </script>

</body>

</html>