<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_admin();

$pdo = db();

/* ---------- Удаление автомобиля ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();

    $carId   = (int) ($_POST['car_id'] ?? 0);
    $deleted = false;
    $error   = 'general';

    if ($carId > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM cars WHERE id = ?');
            $stmt->execute([$carId]);

            if ($stmt->rowCount() > 0) {
                $deleted = true;
            } else {
                $error = 'notfound';
            }
        } catch (PDOException $e) {
            // 23000 — нарушение внешнего ключа (есть брони/заявки/продажи)
            $error = $e->getCode() === '23000' ? 'fk' : 'general';
        }
    }

    if ($deleted) {
        log_action('car.delete', 'cars', $carId);
        redirect(app_base() . 'admin/cars.php?deleted=1');
    }

    redirect(app_base() . 'admin/cars.php?delete_error=' . urlencode($error));
}

/* ---------- Подписи статусов ---------- */
$statusMap = [
    'available' => ['В наличии', 'pill-confirmed'],
    'reserved'  => ['Забронирован', 'pill-new'],
    'sold'      => ['Продан', 'pill-canceled'],
    'service'   => ['На обслуживании', 'pill-progress'],
];
$statusOptions = [];
foreach ($statusMap as $key => $meta) {
    $statusOptions[$key] = $meta[0];
}

/* ---------- Поиск, фильтр и сортировка ---------- */
$search       = trim((string) ($_GET['search'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$sortParam    = (string) ($_GET['sort'] ?? '');

if (mb_strlen($search) > 100) {
    $search = mb_substr($search, 0, 100);
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
               cfg.engine, cfg.power_hp, cfg.transmission, cfg.fuel, cfg.drive
          FROM cars c
          JOIN car_models m ON m.id = c.model_id
     LEFT JOIN car_config cfg ON cfg.car_id = c.id';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ' . $orderSql;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cars = $stmt->fetchAll();

$isFiltered = ($search !== '' || $statusFilter !== '' || $sortParam !== '');

/* ---------- Форматирование ---------- */

$transmissionLabels = car_transmission_labels();
$fuelLabels         = car_fuel_labels();
$driveLabels        = car_drive_labels();

$fmtMoney  = static fn (float $v): string => number_format($v, 0, ',', ' ') . ' Br';
$fmtNumber = static fn (int $v): string => number_format($v, 0, ',', ' ');

// Путь к изображению, корректный из подкаталога /admin
$imgUrl = static function (?string $path): string {
    $src = car_image_url($path);
    if (preg_match('~^(https?:)?//~i', $src)) {
        return $src;
    }
    return app_base() . ltrim($src, '/');
};

/* ---------- Модальное окно результата ---------- */
$isDeleted    = isset($_GET['deleted']);
$deleteError  = is_string($_GET['delete_error'] ?? null) ? $_GET['delete_error'] : '';
$showModal    = $isDeleted || $deleteError !== '';
$modalMessage = '';

if ($isDeleted) {
    $modalMessage = 'Автомобиль удалён из реестра автосалона.';
} elseif ($deleteError !== '') {
    $modalMessage = match ($deleteError) {
        'fk'       => 'Не удалось удалить автомобиль: по нему есть связанные записи (брони, заявки или продажи).',
        'notfound' => 'Автомобиль не найден — возможно, он уже был удалён.',
        default    => 'Не удалось удалить автомобиль. Попробуйте ещё раз.',
    };
}

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Автомобили — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/custom-select.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <div class="admin-page-head">
            <div>
                <h1 class="admin-title">Автомобили <span>· реестр автосалона</span></h1>
                <p class="admin-subtitle"><?= $isFiltered ? 'Найдено' : 'Всего' ?> автомобилей: <?= count($cars) ?></p>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <a class="a-btn a-btn-primary" href="car_form.php">+ Добавить авто</a>
                <div class="admin-date"><?= date('d.m.Y, H:i') ?></div>
            </div>
        </div>

        <form class="panel users-toolbar-panel" method="get" action="cars.php">
            <div class="panel-body">
                <div class="users-toolbar">
                    <div class="a-field">
                        <label for="search">Поиск по модели или VIN</label>
                        <input type="text" id="search" name="search" class="a-input" maxlength="100"
                               value="<?= e($search) ?>" placeholder="Например, XC60 или VIN">
                    </div>
                    <div class="a-field">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="a-select">
                            <option value="">Все статусы</option>
                            <?php foreach ($statusOptions as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= $statusFilter === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="a-field">
                        <label for="sort">Сортировка</label>
                        <select id="sort" name="sort" class="a-select">
                            <?php foreach ($sortOptions as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= $sortParam === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="users-toolbar-actions">
                        <button type="submit" class="a-btn a-btn-primary">Применить</button>
                        <a class="a-btn a-btn-ghost" href="cars.php">Сбросить</a>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($cars): ?>
            <div class="car-grid">
                <?php foreach ($cars as $car): ?>
                    <?php
                    [$statusText, $statusClass] = $statusMap[$car['status']] ?? [$car['status'], 'pill-canceled'];

                    $specs = [];
                    if (!empty($car['power_hp'])) {
                        $specs[] = (int) $car['power_hp'] . ' л.с.';
                    }
                    if (!empty($car['engine'])) {
                        $specs[] = (string) $car['engine'];
                    }
                    if (!empty($car['transmission'])) {
                        $specs[] = $transmissionLabels[$car['transmission']] ?? (string) $car['transmission'];
                    }
                    if (!empty($car['fuel'])) {
                        $specs[] = $fuelLabels[$car['fuel']] ?? (string) $car['fuel'];
                    }
                    if (!empty($car['drive'])) {
                        $specs[] = $driveLabels[$car['drive']] ?? (string) $car['drive'];
                    }
                    ?>
                    <article class="car-card">
                        <div class="car-card-media">
                            <img src="<?= e($imgUrl($car['main_image'])) ?>"
                                 alt="Volvo <?= e($car['model_name']) ?>" loading="lazy">
                            <span class="pill <?= $statusClass ?> car-card-status"><?= e($statusText) ?></span>
                        </div>

                        <div class="car-card-body">
                            <h2 class="car-card-title">Volvo <?= e($car['model_name']) ?>, <?= e((string) $car['year']) ?></h2>
                            <div class="car-card-sub">
                                <?= e($car['body_type'] ?? '') ?>
                                <?php if (!empty($car['color'])): ?> · <?= e($car['color']) ?><?php endif; ?>
                            </div>

                            <div class="car-card-price"><?= e($fmtMoney((float) $car['price'])) ?></div>

                            <?php if ($specs): ?>
                                <div class="car-card-specs">
                                    <?php foreach ($specs as $spec): ?>
                                        <span class="car-spec-chip"><?= e($spec) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="car-card-vin">
                                VIN: <?= e($car['vin']) ?> ·
                                <?= (int) $car['mileage'] === 0 ? 'Без пробега' : e($fmtNumber((int) $car['mileage']) . ' км') ?>
                            </div>

                            <div class="car-card-actions">
                                <a class="a-btn a-btn-ghost" href="car_edit.php?id=<?= (int) $car['id'] ?>">Редактировать</a>

                                <form method="post" action="cars.php" class="car-delete-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="car_id" value="<?= (int) $car['id'] ?>">
                                    <button type="submit" class="a-btn a-btn-danger">Удалить</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <section class="panel">
                <div class="empty">
                    Ничего не найдено.
                    <?php if ($isFiltered): ?>
                        <a href="cars.php">Сбросить фильтры</a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php if ($showModal): ?>
        <div class="modal" id="carsModal">
            <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="carsModalTitle">
                <button type="button" class="modal-close" data-modal-close aria-label="Закрыть">&times;</button>

                <div class="modal-icon <?= $isDeleted ? 'modal-icon-success' : 'modal-icon-error' ?>">
                    <?php if ($isDeleted): ?>
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                    <?php else: ?>
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    <?php endif; ?>
                </div>

                <h2 class="modal-title" id="carsModalTitle">
                    <?= $isDeleted ? 'Автомобиль удалён' : 'Не удалось удалить' ?>
                </h2>
                <p class="modal-text"><?= e($modalMessage) ?></p>
            </div>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('carsModal');
                if (!modal) {
                    return;
                }

                function closeModal() {
                    modal.hidden = true;
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, '', window.location.pathname);
                    }
                }

                var closeBtn = modal.querySelector('[data-modal-close]');
                if (closeBtn) {
                    closeBtn.addEventListener('click', closeModal);
                }
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modal.hidden) {
                        closeModal();
                    }
                });
            })();
        </script>
    <?php endif; ?>

    <script src="../assets/js/custom-select.js"></script>

</body>

</html>
