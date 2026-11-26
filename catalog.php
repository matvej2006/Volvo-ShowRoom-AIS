<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$statusLabels       = car_status_labels();
$transmissionLabels = car_transmission_labels();
$fuelLabels         = car_fuel_labels();
$driveLabels        = car_drive_labels();

/* ---------- Поиск, статус и сортировка ---------- */
$search       = trim((string) ($_GET['search'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$sortParam    = (string) ($_GET['sort'] ?? '');

if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
}

$statusOptions = [];
foreach ($statusLabels as $key => $meta) {
    $statusOptions[$key] = $meta[0];
}
if (!isset($statusOptions[$statusFilter])) {
    $statusFilter = '';
}

$sortOptions = [
    ''             => 'По умолчанию',
    'model_asc'    => 'Модель: А–Я',
    'model_desc'   => 'Модель: Я–А',
    'year_desc'    => 'Год: новые сначала',
    'year_asc'     => 'Год: старые сначала',
    'price_asc'    => 'Цена: по возрастанию',
    'price_desc'   => 'Цена: по убыванию',
    'mileage_asc'  => 'Пробег: по возрастанию',
    'mileage_desc' => 'Пробег: по убыванию',
];
if (!isset($sortOptions[$sortParam])) {
    $sortParam = '';
}

$where  = [];
$params = [];
if ($search !== '') {
    $like = '%' . addcslashes($search, "\\%_") . '%';
    $where[]  = '(m.name LIKE ? OR c.vin LIKE ?)';
    $params[] = $like;
    $params[] = $like;
}
if ($statusFilter !== '') {
    $where[]  = 'c.status = ?';
    $params[] = $statusFilter;
}

$orderSql = "ORDER BY FIELD(c.status, 'available', 'reserved', 'service', 'sold'), m.name, c.year DESC";
if ($sortParam !== '') {
    $parts = explode('_', $sortParam, 2);
    $sortColumns = [
        'model'   => 'm.name',
        'year'    => 'c.year',
        'price'   => 'c.price',
        'mileage' => 'c.mileage',
    ];
    $sortColumn = $sortColumns[$parts[0]] ?? 'm.name';
    $sortDirSql = (($parts[1] ?? 'asc') === 'asc') ? 'ASC' : 'DESC';
    $orderSql   = 'ORDER BY ' . $sortColumn . ' ' . $sortDirSql . ', c.id DESC';
}

$sql = 'SELECT c.id, c.vin, c.year, c.price, c.mileage, c.color, c.status, c.main_image,
               m.name AS model_name, m.body_type,
               cfg.engine, cfg.power_hp, cfg.transmission, cfg.fuel, cfg.drive, cfg.trim
          FROM cars c
          JOIN car_models m ON m.id = c.model_id
     LEFT JOIN car_config cfg ON cfg.car_id = c.id';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ' . $orderSql;

$stmt = db()->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

$availableCount = 0;
foreach ($cars as $car) {
    if ($car['status'] === 'available') {
        $availableCount++;
    }
}

$isFiltered = ($search !== '' || $statusFilter !== '' || $sortParam !== '');

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
    <link rel="stylesheet" href="assets/css/custom-select.css">
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
                    <?= $isFiltered ? 'Найдено' : 'Всего в каталоге' ?>: <?= count($cars) ?> · в наличии: <?= $availableCount ?>
                </span>
            </div>

            <form class="catalog-toolbar" method="get" action="catalog.php">
                <div class="field">
                    <label for="search">Поиск по модели или VIN</label>
                    <input type="text" id="search" name="search" maxlength="100"
                           value="<?= e($search) ?>" placeholder="Например, XC60">
                </div>
                <div class="field">
                    <label for="status">Статус</label>
                    <select id="status" name="status">
                        <option value="">Все статусы</option>
                        <?php foreach ($statusOptions as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $statusFilter === $val ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="sort">Сортировка</label>
                    <select id="sort" name="sort">
                        <?php foreach ($sortOptions as $val => $label): ?>
                            <option value="<?= e($val) ?>" <?= $sortParam === $val ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="catalog-toolbar-actions">
                    <button type="submit" class="btn btn-dark">Применить</button>
                    <a class="btn btn-outline-dark" href="catalog.php">Сбросить</a>
                </div>
            </form>

            <?php if ($cars === []): ?>
                <div class="catalog-empty">
                    <?php if ($isFiltered): ?>
                        <h2>Ничего не найдено</h2>
                        <p>Попробуйте изменить поиск или сбросить фильтры.</p>
                        <a href="catalog.php" class="btn btn-dark">Сбросить фильтры</a>
                    <?php else: ?>
                        <h2>Пока нет автомобилей</h2>
                        <p>Каталог пуст. Позвоните нам — расскажем о ближайших поставках.</p>
                        <a href="contacts.php" class="btn btn-dark">Контакты салона</a>
                    <?php endif; ?>
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
    <script src="assets/js/custom-select.js"></script>

</body>

</html>
