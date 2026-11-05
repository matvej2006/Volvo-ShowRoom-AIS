<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

$fuelLabels     = car_fuel_labels();
$transmissionLabels = car_transmission_labels();

$electricCars = db()->query(
    'SELECT c.id, c.year, c.price, c.mileage, c.color, c.status, c.main_image,
            m.name AS model_name, m.body_type,
            cfg.engine, cfg.power_hp, cfg.transmission, cfg.drive, cfg.trim
       FROM cars c
       JOIN car_models m ON m.id = c.model_id
  LEFT JOIN car_config cfg ON cfg.car_id = c.id
      WHERE c.status = \'available\'
        AND cfg.fuel = \'electric\'
   ORDER BY m.name, c.price ASC'
)->fetchAll();

$fmtMoney  = static fn ($v): string => number_format((float) $v, 0, ',', ' ') . ' ₽';
$fmtNumber = static fn ($v): string => number_format((float) $v, 0, ',', ' ');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Электромобили Volvo — VolvoCarsBy</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php
    if (is_logged()) {
        require __DIR__ . '/includes/logged_header.php';
    } else {
        require __DIR__ . '/includes/header.php';
    }
    ?>

    <!-- Hero -->
    <section class="electro-hero">
        <div class="electro-hero-bg"></div>
        <div class="electro-hero-overlay"></div>
        <div class="container">
            <div class="electro-hero-content">
                <span class="section-label electro-label">Будущее Volvo</span>
                <h1>Полностью <strong>электрические</strong></h1>
                <p>Volvo стремится к тому, чтобы к 2030 году стать производителем исключительно электромобилей. Нулевые выбросы, мгновенный отклик и бесшумное вождение — без компромиссов в безопасности и комфорте.</p>
                <a href="#electro-models" class="btn btn-primary">Смотреть модели</a>
            </div>
        </div>
    </section>

    <!-- Electric models -->
    <section class="section" id="electro-models">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Электрическая линейка</span>
                <h2 class="section-title">Электромобили <strong>Volvo</strong></h2>
                <p class="section-subtitle">Каждый электромобиль Volvo сочетает скандинавский дизайн с передовыми технологиями и нулевым уровнем выбросов.</p>
            </div>

            <?php if ($electricCars === []): ?>
                <div class="electro-empty">
                    <p>Электрические модели временно отсутствуют. Свяжитесь с нами — расскажем о ближайших поставках.</p>
                    <a href="contacts.php" class="btn btn-dark">Контакты салона</a>
                </div>
            <?php else: ?>
                <div class="catalog-list">
                    <?php foreach ($electricCars as $car): ?>
                        <?php
                        $modelName = 'Volvo ' . $car['model_name'];
                        $engineText = !empty($car['engine'])
                            ? e($car['engine'])
                            : ($fuelLabels[$car['fuel'] ?? ''] ?? '—');
                        ?>
                        <a class="catalog-card"
                           href="car.php?id=<?= (int) $car['id'] ?>"
                           aria-label="<?= e($modelName . ', ' . $car['year']) ?>">

                            <div class="catalog-photo">
                                <img src="<?= e(car_image_url($car['main_image'])) ?>"
                                     alt="<?= e($modelName) ?>" loading="lazy">
                            </div>

                            <div class="catalog-body">
                                <div class="catalog-head">
                                    <div class="catalog-heading">
                                        <span class="car-badge car-badge-available">В наличии</span>
                                        <h2 class="catalog-title">Volvo <strong><?= e($car['model_name']) ?></strong></h2>
                                    </div>
                                    <span class="catalog-price"><?= e($fmtMoney($car['price'])) ?></span>
                                </div>

                                <p class="catalog-subtitle">
                                    <?= e($car['year']) ?> год
                                    <?php if (!empty($car['body_type'])): ?>
                                        <span class="car-dot">·</span> <?= e($car['body_type']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($car['color'])): ?>
                                        <span class="car-dot">·</span> <?= e($car['color']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($car['trim'])): ?>
                                        <span class="car-dot">·</span> <?= e($car['trim']) ?>
                                    <?php endif; ?>
                                </p>

                                <div class="catalog-specs">
                                    <div class="catalog-spec">
                                        <span class="spec-value"><?= !empty($car['power_hp']) ? e((int) $car['power_hp'] . ' л.с.') : '—' ?></span>
                                        <span class="spec-label">Мощность</span>
                                    </div>
                                    <div class="catalog-spec">
                                        <span class="spec-value catalog-spec-text"><?= e($engineText) ?></span>
                                        <span class="spec-label">Двигатель</span>
                                    </div>
                                    <div class="catalog-spec">
                                        <span class="spec-value catalog-spec-text"><?= e($transmissionLabels[$car['transmission'] ?? ''] ?? '—') ?></span>
                                        <span class="spec-label">Коробка</span>
                                    </div>
                                    <div class="catalog-spec">
                                        <span class="spec-value catalog-spec-text">Электро</span>
                                        <span class="spec-label">Топливо</span>
                                    </div>
                                </div>

                                <div class="catalog-foot">
                                    <span class="catalog-meta">
                                        Пробег: <?= (int) $car['mileage'] === 0 ? 'новый автомобиль' : e($fmtNumber($car['mileage']) . ' км') ?>
                                    </span>
                                    <span class="catalog-more">Подробнее →</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Benefits -->
    <section class="section bg-dark" id="electro-benefits">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Преимущества</span>
                <h2 class="section-title">Почему <strong>электромобиль</strong></h2>
                <p class="section-subtitle">Переход на электромобиль — это не только забота об экологии, но и новый уровень комфорта и экономии.</p>
            </div>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                    </div>
                    <h3>Мгновенный отклик</h3>
                    <p>Электродвигатель выдаёт максимальный крутящий момент с первых оборотов — разгон плавный и мощный.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>
                        </svg>
                    </div>
                    <h3>Нулевые выбросы</h3>
                    <p>Электромобили Volvo не производят выхлопных газов — чистый воздух для вас и будущих поколений.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <h3>Тишина в салоне</h3>
                    <p>Без двигателя внутреннего сгорания в салоне слышен только ветер и ваша любимая музыка.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                    </div>
                    <h3>Экономия на топливе</h3>
                    <p>Стоимость зарядки значительно ниже расходов на бензин или дизель, а обслуживание проще и дешевле.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <h3>Зарядная инфраструктура</h3>
                    <p>Растущая сеть зарядных станций по всей стране. Заряжайтесь дома, на работе или в пути.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                        </svg>
                    </div>
                    <h3>Технологии будущего</h3>
                    <p>Постоянные обновления по воздуху, интеллектуальные ассистенты и интеграция с вашим смартфоном.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Charging banner -->
    <section class="banner" id="electro-charge">
        <div class="banner-content">
            <h2>Зарядка <strong>проще, чем кажется</strong></h2>
            <p>Домашняя зарядная станция, быстрая зарядка в пути и удобное приложение для планирования маршрута — Volvo делает переход на электротягу максимально комфортным.</p>
            <button onclick="location.href='contacts.php'" class="btn btn-primary">Связаться с нами</button>
        </div>
    </section>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>