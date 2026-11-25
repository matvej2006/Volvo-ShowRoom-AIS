<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_admin();

$pdo = db();

/* ---------- Идентификатор автомобиля ---------- */
$carId = (int) ($_POST['id'] ?? 0);
if ($carId <= 0) {
    $carId = (int) (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
}

/* ---------- Загрузка автомобиля и справочника моделей ---------- */
$car = null;
if ($carId > 0) {
    $stmt = $pdo->prepare(
        'SELECT c.*, m.name AS model_name, m.body_type,
                cfg.engine, cfg.engine_volume, cfg.power_hp, cfg.transmission,
                cfg.fuel, cfg.drive, cfg.trim, cfg.options
           FROM cars c
           JOIN car_models m ON m.id = c.model_id
      LEFT JOIN car_config cfg ON cfg.car_id = c.id
          WHERE c.id = ?
          LIMIT 1'
    );
    $stmt->execute([$carId]);
    $car = $stmt->fetch() ?: null;
}

$models = $pdo->query('SELECT id, name, body_type FROM car_models ORDER BY name')->fetchAll();

/* ---------- Справочники значений ---------- */
$statusOptions = [
    'available' => 'В наличии',
    'reserved'  => 'Забронирован',
    'sold'      => 'Продан',
    'service'   => 'На обслуживании',
];
$statusPills = [
    'available' => ['В наличии', 'pill-confirmed'],
    'reserved'  => ['Забронирован', 'pill-new'],
    'sold'      => ['Продан', 'pill-canceled'],
    'service'   => ['На обслуживании', 'pill-progress'],
];
$transmissionOptions = car_transmission_labels();
$fuelOptions         = car_fuel_labels();
$driveOptions        = car_drive_labels();

/* ---------- Значения формы ---------- */
$values = [
    'model_id'      => $car ? (int) $car['model_id'] : 0,
    'vin'           => $car['vin'] ?? '',
    'year'          => $car['year'] ?? '',
    'price'         => $car ? (float) $car['price'] : '',
    'mileage'       => $car['mileage'] ?? 0,
    'color'         => $car['color'] ?? '',
    'status'        => $car['status'] ?? 'available',
    'main_image'    => $car['main_image'] ?? '',
    'description'   => $car['description'] ?? '',
    'engine'        => $car['engine'] ?? '',
    'engine_volume' => $car['engine_volume'] ?? '',
    'power_hp'      => $car['power_hp'] ?? '',
    'transmission'  => $car['transmission'] ?? '',
    'fuel'          => $car['fuel'] ?? '',
    'drive'         => $car['drive'] ?? '',
    'trim'          => $car['trim'] ?? '',
    'options'       => $car['options'] ?? '',
];

$errors = [];

/* ---------- Сохранение ---------- */
if ($car !== null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $values['model_id']      = (int) ($_POST['model_id'] ?? 0);
    // VIN неизменяем: всегда используем сохранённое значение
    $values['vin']           = (string) $car['vin'];
    $values['year']          = trim((string) ($_POST['year'] ?? ''));
    $values['price']         = trim((string) ($_POST['price'] ?? ''));
    $values['mileage']       = trim((string) ($_POST['mileage'] ?? ''));
    $values['color']         = trim((string) ($_POST['color'] ?? ''));
    $values['status']        = (string) ($_POST['status'] ?? '');
    $values['main_image']    = trim((string) ($_POST['main_image'] ?? ''));
    $values['description']   = trim((string) ($_POST['description'] ?? ''));
    $values['engine']        = trim((string) ($_POST['engine'] ?? ''));
    $values['engine_volume'] = trim((string) ($_POST['engine_volume'] ?? ''));
    $values['power_hp']      = trim((string) ($_POST['power_hp'] ?? ''));
    $values['transmission']  = (string) ($_POST['transmission'] ?? '');
    $values['fuel']          = (string) ($_POST['fuel'] ?? '');
    $values['drive']         = (string) ($_POST['drive'] ?? '');
    $values['trim']          = trim((string) ($_POST['trim'] ?? ''));
    $values['options']       = trim((string) ($_POST['options'] ?? ''));

    // Модель — только из справочника car_models
    $modelIds = array_map(static fn (array $m): int => (int) $m['id'], $models);
    if (!in_array($values['model_id'], $modelIds, true)) {
        $errors['model_id'] = 'Выберите модель из списка.';
    }

    // VIN неизменяем: не валидируем и не сохраняем изменения — остаётся исходный.

    // Защита исторических записей: модель нельзя менять, если авто уже есть в продажах/бронях/заявках.
    $modelChanged = (int) $car['model_id'] !== (int) $values['model_id'];

    if ($modelChanged) {
        $stmt = $pdo->prepare(
            'SELECT
                (SELECT COUNT(*) FROM sales       WHERE car_id = ?) AS sales_cnt,
                (SELECT COUNT(*) FROM bookings    WHERE car_id = ?) AS bookings_cnt,
                (SELECT COUNT(*) FROM test_drives WHERE car_id = ?) AS leads_cnt'
        );
        $stmt->execute([$carId, $carId, $carId]);
        $refs = $stmt->fetch() ?: [];
        $hasRefs = ((int) ($refs['sales_cnt'] ?? 0)
                  + (int) ($refs['bookings_cnt'] ?? 0)
                  + (int) ($refs['leads_cnt'] ?? 0)) > 0;

        if ($hasRefs && !isset($errors['model_id'])) {
            $errors['model_id'] = 'Нельзя менять модель: по автомобилю есть связанные записи.';
        }
    }

    // Год
    $year = (int) $values['year'];
    if ($year < 1901 || $year > 2155) {
        $errors['year'] = 'Год должен быть в диапазоне 1901–2155.';
    }

    // Цена
    $price = 0.0;
    $priceRaw = preg_replace('/[^0-9,.\-]/', '', (string) $values['price']);
    $priceRaw = str_replace(',', '.', (string) $priceRaw);
    if ($priceRaw === '' || !is_numeric($priceRaw)) {
        $errors['price'] = 'Укажите корректную цену.';
    } else {
        $price = round((float) $priceRaw, 2);
        if ($price < 0 || $price > 9999999999.99) {
            $errors['price'] = 'Цена вне допустимого диапазона.';
        }
    }

    // Пробег
    $mileage = 0;
    if (!ctype_digit((string) $values['mileage'])) {
        $errors['mileage'] = 'Пробег должен быть целым неотрицательным числом.';
    } else {
        $mileage = (int) $values['mileage'];
    }

    // Статус
    if (!isset($statusOptions[$values['status']])) {
        $errors['status'] = 'Недопустимый статус.';
    }

    // Согласованность статуса с таблицей продаж (проверяем только при смене статуса)
    $statusChanged = (string) $car['status'] !== $values['status'];
    if (!isset($errors['status']) && $statusChanged) {
        $stmt = $pdo->prepare('SELECT 1 FROM sales WHERE car_id = ? LIMIT 1');
        $stmt->execute([$carId]);
        $hasSale = (bool) $stmt->fetchColumn();

        if ($values['status'] === 'sold' && !$hasSale) {
            $errors['status'] = 'Статус «Продан» устанавливается только через оформление продажи.';
        } elseif ($values['status'] === 'available' && $hasSale) {
            $errors['status'] = 'Автомобиль уже продан — статус «В наличии» недоступен.';
        }
    }

    // Длины строковых полей
    if (mb_strlen($values['color']) > 40) {
        $errors['color'] = 'Не более 40 символов.';
    }
    if (mb_strlen($values['main_image']) > 255) {
        $errors['main_image'] = 'Не более 255 символов.';
    }
    if ($values['engine'] !== '' && mb_strlen($values['engine']) > 80) {
        $errors['engine'] = 'Не более 80 символов.';
    }
    if (mb_strlen($values['trim']) > 60) {
        $errors['trim'] = 'Не более 60 символов.';
    }

    // Комплектация
    $engineVolume = null;
    if ($values['engine_volume'] !== '') {
        $evRaw = preg_replace('/[^0-9,.\-]/', '', $values['engine_volume']);
        $evRaw = str_replace(',', '.', (string) $evRaw);
        if ($evRaw === '' || !is_numeric($evRaw) || (float) $evRaw < 0 || (float) $evRaw > 99.9) {
            $errors['engine_volume'] = 'Объём от 0 до 99.9 л.';
        } else {
            $engineVolume = round((float) $evRaw, 1);
        }
    }

    $powerHp = null;
    if ($values['power_hp'] !== '') {
        if (!ctype_digit((string) $values['power_hp']) || (int) $values['power_hp'] > 32767) {
            $errors['power_hp'] = 'Целое число от 0 до 32767.';
        } else {
            $powerHp = (int) $values['power_hp'];
        }
    }

    if ($values['transmission'] !== '' && !isset($transmissionOptions[$values['transmission']])) {
        $errors['transmission'] = 'Недопустимое значение.';
    }
    if ($values['fuel'] !== '' && !isset($fuelOptions[$values['fuel']])) {
        $errors['fuel'] = 'Недопустимое значение.';
    }
    if ($values['drive'] !== '' && !isset($driveOptions[$values['drive']])) {
        $errors['drive'] = 'Недопустимое значение.';
    }

    if ($errors === []) {
        $saved = false;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'UPDATE cars
                    SET model_id = ?, vin = ?, year = ?, price = ?, mileage = ?,
                        color = ?, description = ?, status = ?, main_image = ?
                  WHERE id = ?'
            );
            $stmt->execute([
                $values['model_id'],
                $values['vin'],
                $year,
                $price,
                $mileage,
                $values['color'] !== '' ? $values['color'] : null,
                $values['description'] !== '' ? $values['description'] : null,
                $values['status'],
                $values['main_image'] !== '' ? $values['main_image'] : null,
                $carId,
            ]);

            $configParams = [
                $values['engine'] !== '' ? $values['engine'] : null,
                $engineVolume,
                $powerHp,
                $values['transmission'] !== '' ? $values['transmission'] : null,
                $values['fuel'] !== '' ? $values['fuel'] : null,
                $values['drive'] !== '' ? $values['drive'] : null,
                $values['trim'] !== '' ? $values['trim'] : null,
                $values['options'] !== '' ? $values['options'] : null,
            ];

            $stmt = $pdo->prepare('SELECT id FROM car_config WHERE car_id = ? LIMIT 1');
            $stmt->execute([$carId]);

            if ($stmt->fetchColumn()) {
                $stmt = $pdo->prepare(
                    'UPDATE car_config
                        SET engine = ?, engine_volume = ?, power_hp = ?, transmission = ?,
                            fuel = ?, drive = ?, trim = ?, options = ?
                      WHERE car_id = ?'
                );
                $stmt->execute(array_merge($configParams, [$carId]));
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO car_config
                        (engine, engine_volume, power_hp, transmission, fuel, drive, trim, options, car_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute(array_merge($configParams, [$carId]));
            }

            $pdo->commit();
            $saved = true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                $errors['general'] = 'Не удалось сохранить: конфликт данных. Возможно, запись уже изменена — обновите страницу.';
            } else {
                $errors['general'] = 'Не удалось сохранить изменения. Попробуйте ещё раз.';
            }
        }

        if ($saved) {
            log_action('car.update', 'cars', $carId);
            flash('success', 'Изменения сохранены.');
            redirect(app_base() . 'admin/car_edit.php?id=' . $carId);
        }
    }
}

$flashSuccess = flash('success');

/* ---------- Вспомогательные значения для шаблона ---------- */
$fmtMoney = static fn (float $v): string => number_format($v, 0, ',', ' ') . ' ₽';

$imgUrl = static function (?string $path): string {
    $src = car_image_url($path);
    if (preg_match('~^(https?:)?//~i', $src)) {
        return $src;
    }
    return app_base() . ltrim($src, '/');
};

$previewModelName = '';
foreach ($models as $m) {
    if ((int) $m['id'] === (int) $values['model_id']) {
        $previewModelName = (string) $m['name'];
        break;
    }
}

[$statusLabel, $statusClass] = $statusPills[$values['status']] ?? ['—', 'pill-canceled'];

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Редактирование авто — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <?php if ($car === null): ?>
            <div class="admin-page-head">
                <div>
                    <h1 class="admin-title">Автомобиль не найден</h1>
                    <p class="admin-subtitle">Возможно, запись была удалена или указан неверный идентификатор.</p>
                </div>
                <a class="a-btn a-btn-ghost" href="cars.php">← К списку авто</a>
            </div>
            <section class="panel">
                <div class="empty">В реестре нет такого автомобиля. <a href="cars.php">Вернуться к списку</a></div>
            </section>
        <?php else: ?>

            <div class="admin-page-head">
                <div>
                    <h1 class="admin-title">Редактирование <span>· Volvo <?= e($car['model_name']) ?></span></h1>
                    <p class="admin-subtitle">VIN <?= e($car['vin']) ?></p>
                </div>
                <a class="a-btn a-btn-ghost" href="cars.php">← К списку авто</a>
            </div>

            <?php if ($flashSuccess !== null): ?>
                <div class="a-alert a-alert-success"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <div class="a-form-grid">
                <form class="a-form-card" id="carEditForm" method="post" action="car_edit.php?id=<?= (int) $carId ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $carId ?>">

                    <?php if (isset($errors['general'])): ?>
                        <div class="a-alert a-alert-error"><?= e($errors['general']) ?></div>
                    <?php elseif ($errors !== []): ?>
                        <div class="a-alert a-alert-error">Проверьте выделенные поля.</div>
                    <?php endif; ?>

                    <section class="a-form-section">
                        <h2>Основное</h2>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label>Марка</label>
                                <input type="text" class="a-input" value="Volvo" disabled>
                                <span class="a-field-hint">Марка фиксирована; изменяется только модель.</span>
                            </div>
                            <div class="a-field">
                                <label for="model_id">Модель</label>
                                <select id="model_id" name="model_id" class="a-select" required>
                                    <?php foreach ($models as $m): ?>
                                        <option value="<?= (int) $m['id'] ?>"
                                                data-name="<?= e($m['name']) ?>"
                                                <?= (int) $values['model_id'] === (int) $m['id'] ? 'selected' : '' ?>>
                                            <?= e($m['name']) ?><?= !empty($m['body_type']) ? ' — ' . e($m['body_type']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['model_id'])): ?>
                                    <span class="a-field-error"><?= e($errors['model_id']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="status">Статус</label>
                                <select id="status" name="status" class="a-select">
                                    <?php foreach ($statusOptions as $val => $label): ?>
                                        <option value="<?= e($val) ?>" <?= $values['status'] === $val ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <span class="a-field-error"><?= e($errors['status']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="a-field">
                                <label for="vin">VIN</label>
                                <input type="text" id="vin" name="vin" class="a-input"
                                       value="<?= e((string) $car['vin']) ?>" readonly>
                                <span class="a-field-hint">VIN присваивается при добавлении и не редактируется.</span>
                            </div>
                        </div>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="year">Год выпуска</label>
                                <input type="number" id="year" name="year" class="a-input" min="1901" max="2155"
                                       value="<?= e((string) $values['year']) ?>" required>
                                <?php if (isset($errors['year'])): ?>
                                    <span class="a-field-error"><?= e($errors['year']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="a-field">
                                <label for="price">Цена, ₽</label>
                                <input type="text" id="price" name="price" class="a-input" inputmode="decimal"
                                       value="<?= e((string) $values['price']) ?>" required>
                                <?php if (isset($errors['price'])): ?>
                                    <span class="a-field-error"><?= e($errors['price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="mileage">Пробег, км</label>
                                <input type="number" id="mileage" name="mileage" class="a-input" min="0"
                                       value="<?= e((string) $values['mileage']) ?>" required>
                                <?php if (isset($errors['mileage'])): ?>
                                    <span class="a-field-error"><?= e($errors['mileage']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="a-field">
                                <label for="color">Цвет</label>
                                <input type="text" id="color" name="color" class="a-input" maxlength="40"
                                       value="<?= e((string) $values['color']) ?>">
                                <?php if (isset($errors['color'])): ?>
                                    <span class="a-field-error"><?= e($errors['color']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="a-field">
                            <label for="main_image">Главное изображение</label>
                            <input type="text" id="main_image" name="main_image" class="a-input" maxlength="255"
                                   value="<?= e((string) $values['main_image']) ?>">
                            <?php if (isset($errors['main_image'])): ?>
                                <span class="a-field-error"><?= e($errors['main_image']) ?></span>
                            <?php else: ?>
                                <span class="a-field-hint">URL, путь <code>assets/...</code> или имя файла в <code>assets/uploads</code>.</span>
                            <?php endif; ?>
                        </div>

                        <div class="a-field">
                            <label for="description">Описание</label>
                            <textarea id="description" name="description" class="a-textarea" rows="4"><?= e((string) $values['description']) ?></textarea>
                        </div>
                    </section>

                    <section class="a-form-section">
                        <h2>Комплектация</h2>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="engine">Двигатель</label>
                                <input type="text" id="engine" name="engine" class="a-input" maxlength="80"
                                       value="<?= e((string) $values['engine']) ?>">
                                <?php if (isset($errors['engine'])): ?>
                                    <span class="a-field-error"><?= e($errors['engine']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="a-field">
                                <label for="engine_volume">Объём, л</label>
                                <input type="text" id="engine_volume" name="engine_volume" class="a-input"
                                       inputmode="decimal" value="<?= e((string) $values['engine_volume']) ?>">
                                <?php if (isset($errors['engine_volume'])): ?>
                                    <span class="a-field-error"><?= e($errors['engine_volume']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="power_hp">Мощность, л.с.</label>
                                <input type="number" id="power_hp" name="power_hp" class="a-input" min="0" max="32767"
                                       value="<?= e((string) $values['power_hp']) ?>">
                                <?php if (isset($errors['power_hp'])): ?>
                                    <span class="a-field-error"><?= e($errors['power_hp']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="a-field">
                                <label for="trim">Комплектация</label>
                                <input type="text" id="trim" name="trim" class="a-input" maxlength="60"
                                       value="<?= e((string) $values['trim']) ?>">
                                <?php if (isset($errors['trim'])): ?>
                                    <span class="a-field-error"><?= e($errors['trim']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="transmission">Коробка передач</label>
                                <select id="transmission" name="transmission" class="a-select">
                                    <option value="">— не указано —</option>
                                    <?php foreach ($transmissionOptions as $val => $label): ?>
                                        <option value="<?= e($val) ?>" <?= $values['transmission'] === $val ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['transmission'])): ?>
                                    <span class="a-field-error"><?= e($errors['transmission']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="a-field">
                                <label for="fuel">Топливо</label>
                                <select id="fuel" name="fuel" class="a-select">
                                    <option value="">— не указано —</option>
                                    <?php foreach ($fuelOptions as $val => $label): ?>
                                        <option value="<?= e($val) ?>" <?= $values['fuel'] === $val ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['fuel'])): ?>
                                    <span class="a-field-error"><?= e($errors['fuel']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="a-form-row">
                            <div class="a-field">
                                <label for="drive">Привод</label>
                                <select id="drive" name="drive" class="a-select">
                                    <option value="">— не указано —</option>
                                    <?php foreach ($driveOptions as $val => $label): ?>
                                        <option value="<?= e($val) ?>" <?= $values['drive'] === $val ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['drive'])): ?>
                                    <span class="a-field-error"><?= e($errors['drive']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div></div>
                        </div>

                        <div class="a-field">
                            <label for="options">Опции</label>
                            <textarea id="options" name="options" class="a-textarea" rows="3"><?= e((string) $values['options']) ?></textarea>
                        </div>
                    </section>

                    <div class="a-form-actions">
                        <a class="a-btn a-btn-ghost" href="cars.php">Отмена</a>
                        <button type="submit" class="a-btn a-btn-primary">Сохранить изменения</button>
                    </div>
                </form>

                <aside class="a-side">
                    <div class="a-preview">
                        <div class="a-preview-media">
                            <img id="previewImage" src="<?= e($imgUrl($values['main_image'])) ?>" alt="">
                            <span class="pill <?= $statusClass ?> car-card-status" id="previewStatus"><?= e($statusLabel) ?></span>
                        </div>
                        <div class="a-preview-body">
                            <span class="a-preview-label">Предпросмотр</span>
                            <strong class="a-preview-name" id="previewName">Volvo <?= e($previewModelName) ?></strong>
                            <span class="a-preview-meta" id="previewMeta">
                                <?= e((string) $values['year']) ?> · <?= e($fmtMoney((float) $values['price'])) ?>
                            </span>
                            <a class="a-preview-link" href="../car.php?id=<?= (int) $carId ?>" target="_blank" rel="noopener">
                                Открыть на сайте →
                            </a>
                        </div>
                    </div>

                    <div class="a-note">
                        <h3>Что можно менять</h3>
                        <p>Марка <strong>Volvo</strong> фиксирована. Модель выбирается из справочника — новые модели здесь не создаются.</p>
                        <p>VIN присваивается при добавлении и не редактируется.</p>
                        <p>Статус «Продан» устанавливается оформлением продажи; вернуть «В наличии» у проданного авто нельзя.</p>
                        <p>Комплектация (двигатель, КПП, топливо, привод, опции) сохраняется в отдельной таблице и обновляется вместе с авто.</p>
                    </div>
                </aside>
            </div>

            <script>
                (function () {
                    var form = document.getElementById('carEditForm');
                    if (!form) {
                        return;
                    }

                    var base = <?= json_encode(app_base(), JSON_UNESCAPED_SLASHES) ?>;

                    var modelSel   = document.getElementById('model_id');
                    var yearInput  = document.getElementById('year');
                    var priceInput = document.getElementById('price');
                    var statusSel  = document.getElementById('status');
                    var imageInput = document.getElementById('main_image');

                    var pName   = document.getElementById('previewName');
                    var pMeta   = document.getElementById('previewMeta');
                    var pStatus = document.getElementById('previewStatus');
                    var pImage  = document.getElementById('previewImage');

                    var statusMap = {
                        available: ['В наличии', 'pill-confirmed'],
                        reserved:  ['Забронирован', 'pill-new'],
                        sold:      ['Продан', 'pill-canceled'],
                        service:   ['На обслуживании', 'pill-progress']
                    };

                    function money(value) {
                        var s = String(value || '').replace(/[^\d.,]/g, '').replace(',', '.');
                        var n = parseFloat(s);
                        if (isNaN(n)) {
                            return '—';
                        }
                        return n.toLocaleString('ru-RU', { maximumFractionDigits: 0 }) + ' ₽';
                    }

                    function imgSrc(path) {
                        path = String(path || '').trim();
                        if (!path) {
                            return base + 'assets/images/new_hero_xc60.jpg';
                        }
                        if (/^https?:\/\//i.test(path)) {
                            return path;
                        }
                        if (path.indexOf('assets/') === 0) {
                            return base + path;
                        }
                        return base + 'assets/uploads/' + path.replace(/^\/+/, '');
                    }

                    function updatePreview() {
                        var opt = modelSel ? modelSel.options[modelSel.selectedIndex] : null;
                        var modelName = opt ? (opt.getAttribute('data-name') || opt.textContent.trim()) : '';

                        pName.textContent = 'Volvo ' + modelName;

                        var year = yearInput ? yearInput.value : '';
                        pMeta.textContent = (year ? year + ' · ' : '') + money(priceInput ? priceInput.value : '');

                        var st = statusMap[statusSel ? statusSel.value : ''] || ['—', 'pill-canceled'];
                        pStatus.textContent = st[0];
                        pStatus.className = 'pill ' + st[1] + ' car-card-status';

                        pImage.src = imgSrc(imageInput ? imageInput.value : '');
                    }

                    [modelSel, yearInput, priceInput, statusSel, imageInput].forEach(function (el) {
                        if (el) {
                            el.addEventListener('input', updatePreview);
                            el.addEventListener('change', updatePreview);
                        }
                    });

                    updatePreview();
                })();
            </script>

        <?php endif; ?>

    </main>

</body>

</html>
