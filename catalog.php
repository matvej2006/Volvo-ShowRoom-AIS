<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$statusLabels       = car_status_labels();
$transmissionLabels = car_transmission_labels();
$fuelLabels         = car_fuel_labels();
$driveLabels        = car_drive_labels();

$stmt = db()->query(
    'SELECT c.id, c.vin, c.year, c.price, c.mileage, c.color, c.status, c.main_image,
            m.name AS model_name, m.body_type,
            cfg.engine, cfg.power_hp, cfg.transmission, cfg.fuel, cfg.drive, cfg.trim
       FROM cars c
       JOIN car_models m ON m.id = c.model_id
  LEFT JOIN car_config cfg ON cfg.car_id = c.id
   ORDER BY FIELD(c.status, \'available\', \'reserved\', \'service\', \'sold\'), m.name, c.year DESC'
);
$cars = $stmt->fetchAll();

$availableCount = 0;
foreach ($cars as $car) {
    if ($car['status'] === 'available') {
        $availableCount++;
    }
}

$fmtMoney  = static fn ($value): string => number_format((float) $value, 0, ',', ' ') . ' Br';
$fmtNumber = static fn ($value): string => number_format((float) $value, 0, ',', ' ');

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Автомобили — VolvoCarsBy</title>
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

    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="index.php">Главная</a>
                <span class="sep">/</span>
                <span>Автомобили</span>
            </nav>

            <div class="section-header page-hero-head">
                <span class="section-label">Каталог</span>
                <h1 class="section-title">Автомобили <strong>в наличии</strong></h1>
                <span class="catalog-count">
                    Всего в каталоге: <?= count($cars) ?> · в наличии: <?= $availableCount ?>
                </span>
            </div>

            <?php if ($cars === []): ?>
                <div class="catalog-empty">
                    <h2>Пока нет автомобилей</h2>
                    <p>Каталог пуст. Позвоните нам — расскажем о ближайших поставках.</p>
                    <a href="contacts.php" class="btn btn-dark">Контакты салона</a>
                </div>
            <?php else: ?>
                <div class="catalog-list">
                    <?php foreach ($cars as $car): ?>
                        <?php
                        [$statusText, $statusClass] = $statusLabels[$car['status']] ?? [$car['status'], 'car-badge-sold'];
                        $modelName = 'Volvo ' . $car['model_name'];
                        $engineText = trim((string) ($car['engine'] ?? ''));
                        if ($engineText === '') {
                            $engineText = $fuelLabels[$car['fuel'] ?? ''] ?? '—';
                        }
                        ?>
                        <a class="catalog-card"
                            href="car.php?id=<?= (int) $car['id'] ?>"
                            aria-label="<?= e($modelName . ', ' . $car['year'] . ' год, ' . $fmtMoney($car['price'])) ?>">

                            <div class="catalog-photo">
                                <img src="<?= e(car_image_url($car['main_image'])) ?>" alt="<?= e($modelName) ?>" loading="lazy">
                            </div>

                            <div class="catalog-body">
                                <div class="catalog-head">
                                    <div class="catalog-heading">
                                        <span class="car-badge <?= e($statusClass) ?>"><?= e($statusText) ?></span>
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
                                        <span class="spec-value catalog-spec-text"><?= e($driveLabels[$car['drive'] ?? ''] ?? '—') ?></span>
                                        <span class="spec-label">Привод</span>
                                    </div>
                                </div>

                                <div class="catalog-foot">
                                    <span class="catalog-meta">
                                        Пробег: <?= (int) $car['mileage'] === 0 ? 'новый автомобиль' : e($fmtNumber($car['mileage']) . ' км') ?>
                                        <span class="car-dot">·</span> <?= e($fuelLabels[$car['fuel'] ?? ''] ?? '—') ?>
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

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>
