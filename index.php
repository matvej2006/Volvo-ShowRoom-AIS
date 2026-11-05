<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="">
    <title>VolvoCarsBy — Официальный дилер</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/main.js"></script>
</head>

<body>

    <?php
    require "config/auth.php";
    if (is_logged()) {
        require "includes/logged_header.php";
    } else {
        require "includes/header.php";
    }
    ?>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="hero-badge">Новый Volvo XC60 2026</span>
            <h1>Создан для вашей жизни</h1>
            <p>Откройте линейку автомобилей Volvo, где скандинавский дизайн сочетается с передовыми технологиями безопасности и электрической мощностью.</p>
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

                <?php
                require_once __DIR__ . '/config/db.php';
                require_once __DIR__ . '/config/functions.php';

                $fuelLabels     = car_fuel_labels();
                $transmissionLabels = car_transmission_labels();
                //TODO: edit car data
                $modelRows = db()->query(
                    'SELECT m.id AS model_id, m.name AS model_name, m.body_type,
                            c.id AS car_id, c.price, c.main_image,
                            cfg.power_hp, cfg.engine, cfg.fuel, cfg.transmission, c.year
                       FROM car_models m
                       JOIN cars c ON c.model_id = m.id AND c.status = \'available\'
                  LEFT JOIN car_config cfg ON cfg.car_id = c.id
                   ORDER BY m.name, c.price ASC'
                )->fetchAll();

                $models = [];
                foreach ($modelRows as $row) {
                    $mid = $row['model_id'];
                    if (!isset($models[$mid])) {
                        $models[$mid] = [
                            'model_name'   => $row['model_name'],
                            'body_type'    => $row['body_type'] ?? '',
                            'min_price'    => $row['price'],
                            'main_image'   => $row['main_image'],
                            'power_hp'     => $row['power_hp'],
                            'engine'       => $row['engine'],
                            'fuel'         => $row['fuel'],
                            'transmission' => $row['transmission'],
                            'year'         => $row['year'],
                            'count'        => 1,
                        ];
                    } else {
                        $models[$mid]['count']++;
                    }
                }

                $fmtMoney = static fn ($v): string => number_format((float) $v, 0, ',', ' ') . ' ₽';
                $fmtNum   = static fn ($v): string => number_format((float) $v, 0, ',', ' ');

                foreach ($models as $m):
                    $isElectric = ($m['fuel'] ?? '') === 'electric';
                    $tagTextEl    = $isElectric ? 'Электрический' : '';
                    $tagClass   = $isElectric ? 'electric' : '';
                    $isNew = ($m['year'] ?? '') > 2025;
                    $tagTextNew = !$isNew ? '' : 'Новый';
                    $powerText  = !empty($m['power_hp']) ? $fmtNum($m['power_hp']) . ' л.с.' : '—';
                    $engineText = !empty($m['engine'])
                        ? e($m['engine'])
                        : ($fuelLabels[$m['fuel'] ?? ''] ?? '—');
                    $transText  = $transmissionLabels[$m['transmission'] ?? ''] ?? '—';
                ?>
                    <div class="model-card">
                        <div class="model-image-wrapper">
                            <img src="<?= e(car_image_url($m['main_image'])) ?>" alt="Volvo <?= e($m['model_name']) ?>" class="model-image" loading="lazy">
                             <?php if ($tagTextEl !== '' || $tagTextNew !== ''): ?>
                                <div class="model-tags">
                                    <?php if ($tagTextEl !== ''): ?>
                                        <span class="model-tag tag-electric"><?= e($tagTextEl) ?></span>
                                    <?php endif; ?>
                                    <?php if ($tagTextNew !== ''): ?>
                                        <span class="model-tag tag-new"><?= e($tagTextNew) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="model-info">
                            <h3 class="model-name"><?= e($m['model_name']) ?></h3>
                            <p class="model-type"><?= e($m['body_type']) ?></p>
                            <div class="model-specs">
                                <div class="spec">
                                    <span class="spec-value"><?= e($powerText) ?></span>
                                    <span class="spec-label">Мощность</span>
                                </div>
                                <div class="spec">
                                    <span class="spec-value"><?= e($engineText) ?></span>
                                    <span class="spec-label">Двигатель</span>
                                </div>
                                <div class="spec">
                                    <span class="spec-value"><?= e($transText) ?></span>
                                    <span class="spec-label">Коробка</span>
                                </div>
                            </div>
                            <p class="model-price">от <span><?= e($fmtMoney($m['min_price'])) ?></span></p>
                            <div class="model-actions">
                                <button onclick="location.href='car.php?model=<?= urlencode($m['model_name']) ?>'" class="btn btn-dark">Подробнее</button>
                                <button onclick="location.href='#'" class="btn btn-outline-dark">Конфигуратор</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <div class="models-all">
                <a href="catalog.php">Все автомобили в наличии →</a>
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
                <button onclick="location.href='electro.php'" class="btn btn-dark">Узнать больше об электромобилях</button>
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
            <a href="test-drive.php" class="btn btn-primary">Записаться сейчас</a>
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
                </div>
                <div class="service-card">
                    <h3>Trade-In</h3>
                    <p>Обменяйте ваш текущий автомобиль на новый Volvo с выгодой. Бесплатная оценка и быстрое оформление сделки.</p>
                </div>
                <div class="service-card">
                    <h3>Кредитование и лизинг</h3>
                    <p>Гибкие финансовые программы от ведущих банков-партнёров. Одобрение за 15 минут, ставки от 4.9% годовых.</p>
                </div>
                <div class="service-card">
                    <h3>Страхование</h3>
                    <p>КАСКО и ОСАГО на специальных условиях для владельцев Volvo. Быстрое урегулирование страховых случаев.</p>
                </div>
                <div class="service-card">
                    <h3>Volvo On Call</h3>
                    <p>Удалённое управление автомобилем через приложение: климат-контроль, блокировка дверей, отслеживание местоположения.</p>
                </div>
                <div class="service-card">
                    <h3>Доставка автомобиля</h3>
                    <p>Доставим ваш новый Volvo прямо к двери. Доступно по всей России с полным пакетом документов.</p>
                </div>
            </div>
        </div>
    </section>

    <?php
    require "includes/footer.php";
    ?>

</body>

</html>