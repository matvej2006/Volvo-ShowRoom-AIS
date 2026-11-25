<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

function car_not_found(): never
{
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$transmissionLabels = car_transmission_labels();
$fuelLabels         = car_fuel_labels();
$driveLabels        = car_drive_labels();
$statusLabels       = car_status_labels();

$carId     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$modelName = isset($_GET['model']) ? trim((string) $_GET['model']) : '';

$sql = 'SELECT c.*, m.name AS model_name, m.body_type,
               cfg.engine, cfg.engine_volume, cfg.power_hp, cfg.transmission,
               cfg.fuel, cfg.drive, cfg.trim, cfg.options
          FROM cars c
          JOIN car_models m ON m.id = c.model_id
     LEFT JOIN car_config cfg ON cfg.car_id = c.id
         WHERE ';

$params = [];
if ($carId) {
    $sql .= 'c.id = ?';
    $params[] = $carId;
} elseif ($modelName !== '') {
    $sql .= 'm.name = ?';
    $params[] = $modelName;
} else {
    car_not_found();
}

$sql .= ' LIMIT 1';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$car = $stmt->fetch();

if (!$car) {
    car_not_found();
}

// Галерея: изображения из car_images, иначе главное фото автомобиля.
$stmt = db()->prepare('SELECT path FROM car_images WHERE car_id = ? ORDER BY sort_order, id');
$stmt->execute([(int) $car['id']]);
$images = array_values(array_filter(array_map(
    static fn(array $row): string => car_image_url($row['path']),
    $stmt->fetchAll()
)));

if ($images === []) {
    $images = [car_image_url($car['main_image'])];
}

// Похожие автомобили: сначала близкие по цене, только в наличии.
$stmt = db()->prepare(
    'SELECT c.id, c.year, c.price, c.main_image, m.name AS model_name
       FROM cars c
       JOIN car_models m ON m.id = c.model_id
      WHERE c.id <> ? AND c.status = \'available\'
   ORDER BY ABS(c.price - ?), c.id
      LIMIT 3'
);
$stmt->execute([(int) $car['id'], (float) $car['price']]);
$similar = $stmt->fetchAll();

$fmtMoney  = static fn($value): string => number_format((float) $value, 0, ',', ' ') . ' Br';
$fmtNumber = static fn($value): string => number_format((float) $value, 0, ',', ' ');

$price = $fmtMoney($car['price']);
$title = 'Volvo ' . $car['model_name'];

$status = $statusLabels[$car['status']] ?? [$car['status'], 'car-badge-sold'];

$mileageText = (int) $car['mileage'] === 0
    ? 'Новый автомобиль'
    : $fmtNumber($car['mileage']) . ' км';

$powerText = !empty($car['power_hp']) ? (int) $car['power_hp'] . ' л.с.' : '—';

$engineText = trim((string) ($car['engine'] ?? ''));
if ($engineText === '') {
    $engineText = $fuelLabels[$car['fuel'] ?? ''] ?? '—';
}

// Характеристики для таблицы: подпись => значение.
$specRows = [
    'Год выпуска'        => (string) $car['year'],
    'Пробег'             => $mileageText,
    'Цвет'               => !empty($car['color']) ? (string) $car['color'] : '—',
    'Тип кузова'         => !empty($car['body_type']) ? (string) $car['body_type'] : '—',
    'Двигатель'          => $engineText,
    'Объём двигателя'    => !empty($car['engine_volume']) ? number_format((float) $car['engine_volume'], 1, ',', '') . ' л' : null,
    'Мощность'           => $powerText,
    'Коробка передач'    => $transmissionLabels[$car['transmission'] ?? ''] ?? '—',
    'Тип топлива'        => $fuelLabels[$car['fuel'] ?? ''] ?? '—',
    'Привод'             => $driveLabels[$car['drive'] ?? ''] ?? '—',
    'Комплектация'       => !empty($car['trim']) ? (string) $car['trim'] : '—',
    'VIN'                => (string) $car['vin'],
];
$specRows = array_filter($specRows, static fn($value): bool => $value !== null);

$options = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) ($car['options'] ?? ''))
)));

$description = trim((string) ($car['description'] ?? ''));
if ($description === '') {
    $description = 'Автомобиль проверен по 128 пунктам и готов к передаче. Полная история обслуживания, '
        . 'официальная гарантия и возможность оформить кредит или лизинг прямо в салоне.';
}

$primaryCta = is_logged()
    ? 'test-drive.php?car=' . (int) $car['id']
    : 'login.php';

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — VolvoCarsBy</title>
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

    <section class="car-page">
        <div class="container">

            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="index.php">Главная</a>
                <span class="sep">/</span>
                <a href="index.php#models">Модели</a>
                <span class="sep">/</span>
                <span><?= e($title) ?></span>
            </nav>

            <div class="car-main">
                <div class="car-gallery">
                    <div class="car-gallery-main">
                        <img id="carMainImage" src="<?= e($images[0]) ?>" alt="<?= e($title) ?>">
                    </div>

                    <?php if (count($images) > 1): ?>
                        <div class="car-gallery-thumbs">
                            <?php foreach ($images as $index => $image): ?>
                                <button type="button"
                                    class="car-thumb <?= $index === 0 ? 'active' : '' ?>"
                                    data-src="<?= e($image) ?>"
                                    aria-label="Фото <?= (int) $index + 1 ?>">
                                    <img src="<?= e($image) ?>" alt="<?= e($title) ?>, фото <?= (int) $index + 1 ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="car-summary">
                    <span class="car-badge <?= e($status[1]) ?>"><?= e($status[0]) ?></span>
                    <p class="car-model-label"><?= e($car['body_type'] ?? 'Volvo') ?></p>
                    <h1 class="car-title">Volvo <strong><?= e($car['model_name']) ?></strong></h1>
                    <p class="car-sub">
                        <?= e($car['year']) ?> год
                        <span class="car-dot">·</span>
                        <?= e($mileageText) ?>
                        <?php if (!empty($car['color'])): ?>
                            <span class="car-dot">·</span>
                            <?= e($car['color']) ?>
                        <?php endif; ?>
                    </p>

                    <div class="car-price">
                        <span class="car-price-label">Цена</span>
                        <span class="car-price-value"><?= e($price) ?></span>
                    </div>

                    <div class="car-quick-specs">
                        <div class="spec">
                            <span class="spec-value"><?= e($powerText) ?></span>
                            <span class="spec-label">Мощность</span>
                        </div>
                        <div class="spec">
                            <span class="spec-value"><?= e($transmissionLabels[$car['transmission'] ?? ''] ?? '—') ?></span>
                            <span class="spec-label">Коробка</span>
                        </div>
                        <div class="spec">
                            <span class="spec-value"><?= e($fuelLabels[$car['fuel'] ?? ''] ?? '—') ?></span>
                            <span class="spec-label">Топливо</span>
                        </div>
                        <div class="spec">
                            <span class="spec-value"><?= e($driveLabels[$car['drive'] ?? ''] ?? '—') ?></span>
                            <span class="spec-label">Привод</span>
                        </div>
                    </div>

                    <div class="car-cta">
                        <?php if (is_logged()): ?>
                            <?php if ($car['status'] === 'available'): ?>
                                <a href="<?= e($primaryCta) ?>" class="btn btn-dark">
                                    Записаться на тест-драйв
                                </a>
                                <a href="booking.php?car=<?= (int) $car['id'] ?>" class="btn btn-outline-dark">
                                    Забронировать
                                </a>
                            <?php else: ?>
                                <a href="#" class="btn btn-dark car-btn-inactive" aria-disabled="true" onclick="return false;">
                                    Записаться на тест-драйв
                                </a>
                                <a href="#" class="btn btn-outline-dark car-btn-inactive" aria-disabled="true" onclick="return false;">
                                    Забронировать
                                </a>
                                <?php if ($car['status'] === 'sold'): ?>
                                    <p class="car-unavailable-note">Этот автомобиль уже продан.</p>
                                <?php elseif ($car['status'] === 'reserved'): ?>
                                    <p class="car-unavailable-note">Автомобиль забронирован.</p>
                                <?php elseif ($car['status'] === 'service'): ?>
                                    <p class="car-unavailable-note">Автомобиль на предпродажной подготовке.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($car['status'] === 'available'): ?>
                                <a href="login.php" class="btn btn-dark">
                                    Записаться на тест-драйв
                                </a>
                                <a href="login.php" class="btn btn-outline-dark">
                                    Забронировать
                                </a>
                            <?php else: ?>
                                <a href="#" class="btn btn-dark car-btn-inactive" aria-disabled="true" onclick="return false;">
                                    Записаться на тест-драйв
                                </a>
                                <a href="#" class="btn btn-outline-dark car-btn-inactive" aria-disabled="true" onclick="return false;">
                                    Забронировать
                                </a>
                                <?php if ($car['status'] === 'sold'): ?>
                                    <p class="car-unavailable-note">Этот автомобиль уже продан.</p>
                                <?php elseif ($car['status'] === 'reserved'): ?>
                                    <p class="car-unavailable-note">Автомобиль забронирован.</p>
                                <?php elseif ($car['status'] === 'service'): ?>
                                    <p class="car-unavailable-note">Автомобиль на предпродажной подготовке.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <p class="car-note">VIN: <?= e($car['vin']) ?></p>
                </aside>
            </div>

            <div class="car-block car-description">
                <h2>Об автомобиле</h2>
                <p><?= e($description) ?></p>
            </div>

            <div class="car-detail-grid">
                <div class="car-block">
                    <h2>Характеристики</h2>
                    <dl class="spec-list">
                        <?php foreach ($specRows as $label => $value): ?>
                            <div class="spec-row">
                                <dt><?= e($label) ?></dt>
                                <dd><?= e($value) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>

                <?php if ($options !== []): ?>
                    <div class="car-block">
                        <h2>Комплектация</h2>
                        <ul class="option-list">
                            <?php foreach ($options as $option): ?>
                                <li><?= e($option) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($similar !== []): ?>
                <div class="car-similar">
                    <div class="car-similar-head">
                        <h2>Похожие автомобили</h2>
                        <a href="catalog.php" class="car-similar-link">Все автомобили →</a>
                    </div>

                    <div class="car-similar-grid">
                        <?php foreach ($similar as $item): ?>
                            <a class="car-mini" href="car.php?id=<?= (int) $item['id'] ?>">
                                <img src="<?= e(car_image_url($item['main_image'])) ?>" alt="Volvo <?= e($item['model_name']) ?>">
                                <div class="car-mini-body">
                                    <span class="car-mini-name">Volvo <?= e($item['model_name']) ?></span>
                                    <span class="car-mini-meta"><?= e($item['year']) ?> год · в наличии</span>
                                    <span class="car-mini-price">от <?= e($fmtMoney($item['price'])) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        const mainImage = document.getElementById('carMainImage');
        document.querySelectorAll('.car-thumb').forEach(thumb => {
            thumb.addEventListener('click', () => {
                mainImage.src = thumb.dataset.src;
                document.querySelectorAll('.car-thumb').forEach(item => item.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    </script>

</body>

</html>