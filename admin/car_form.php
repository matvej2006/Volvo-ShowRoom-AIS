<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_admin();

$pdo = db();

/* ---------- Справочник моделей и типов кузова ---------- */
$modelRows = $pdo->query('SELECT name, body_type FROM car_models ORDER BY name')->fetchAll();

$modelNames = [];
$bodyTypes  = [];
foreach ($modelRows as $modelRow) {
    $modelNames[] = (string) $modelRow['name'];
    $modelBodyType = trim((string) ($modelRow['body_type'] ?? ''));
    if ($modelBodyType !== '' && !in_array($modelBodyType, $bodyTypes, true)) {
        $bodyTypes[] = $modelBodyType;
    }
}
sort($bodyTypes, SORT_NATURAL | SORT_FLAG_CASE);

/* ---------- Справочники значений ---------- */
$statusOptions = [
    'available' => 'В наличии',
    'reserved'  => 'Забронирован',
    'service'   => 'На обслуживании',
];
$statusPills = [
    'available' => ['В наличии', 'pill-confirmed'],
    'reserved'  => ['Забронирован', 'pill-new'],
    'service'   => ['На обслуживании', 'pill-progress'],
    'sold'      => ['Продан', 'pill-canceled'],
];
$transmissionOptions = car_transmission_labels();
$fuelOptions         = car_fuel_labels();
$driveOptions        = car_drive_labels();

/* ---------- Значения формы ---------- */
$values = [
    'model_name'    => '',
    'body_type'     => '',
    'vin'           => '',
    'year'          => '',
    'price'         => '',
    'mileage'       => '0',
    'color'         => '',
    'status'        => 'available',
    'main_image'    => '',
    'description'   => '',
    'engine'        => '',
    'engine_volume' => '',
    'power_hp'      => '',
    'transmission'  => '',
    'fuel'          => '',
    'drive'         => '',
    'trim'          => '',
    'options'       => '',
];

$errors = [];

/* ---------- Сохранение ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $values['model_name']    = trim((string) ($_POST['model_name'] ?? ''));
    $values['body_type']     = trim((string) ($_POST['body_type'] ?? ''));
    $values['vin']           = trim((string) ($_POST['vin'] ?? ''));
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

    // Модель и тип кузова
    if ($values['model_name'] === '') {
        $errors['model_name'] = 'Укажите модель.';
    } elseif (mb_strlen($values['model_name']) > 80) {
        $errors['model_name'] = 'Не более 80 символов.';
    }

    if ($values['body_type'] === '') {
        $errors['body_type'] = 'Укажите тип кузова.';
    } elseif (mb_strlen($values['body_type']) > 40) {
        $errors['body_type'] = 'Не более 40 символов.';
    }

    // VIN — 17 символов, уникальный
    if ($values['vin'] === '') {
        $errors['vin'] = 'Укажите VIN.';
    } elseif (mb_strlen($values['vin']) !== 17) {
        $errors['vin'] = 'VIN должен содержать ровно 17 символов.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM cars WHERE vin = ? LIMIT 1');
        $stmt->execute([$values['vin']]);
        if ($stmt->fetchColumn()) {
            $errors['vin'] = 'Автомобиль с таким VIN уже есть в реестре.';
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
        $newId = 0;

        try {
            $pdo->beginTransaction();

            // Найти или создать модель (тип кузова хранится у модели)
            $stmt = $pdo->prepare('SELECT id, body_type FROM car_models WHERE name = ? LIMIT 1');
            $stmt->execute([$values['model_name']]);
            $existingModel = $stmt->fetch();

            if ($existingModel) {
                $modelId = (int) $existingModel['id'];
                if ((string) ($existingModel['body_type'] ?? '') !== $values['body_type']) {
                    $stmt = $pdo->prepare('UPDATE car_models SET body_type = ? WHERE id = ?');
                    $stmt->execute([$values['body_type'], $modelId]);
                }
            } else {
                $stmt = $pdo->prepare('INSERT INTO car_models (name, body_type) VALUES (?, ?)');
                $stmt->execute([$values['model_name'], $values['body_type']]);
                $modelId = (int) $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                'INSERT INTO cars (model_id, vin, year, price, mileage, color, description, status, main_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $modelId,
                $values['vin'],
                $year,
                $price,
                $mileage,
                $values['color'] !== '' ? $values['color'] : null,
                $values['description'] !== '' ? $values['description'] : null,
                $values['status'],
                $values['main_image'] !== '' ? $values['main_image'] : null,
            ]);

            $newId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO car_config
                    (engine, engine_volume, power_hp, transmission, fuel, drive, trim, options, car_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $values['engine'] !== '' ? $values['engine'] : null,
                $engineVolume,
                $powerHp,
                $values['transmission'] !== '' ? $values['transmission'] : null,
                $values['fuel'] !== '' ? $values['fuel'] : null,
                $values['drive'] !== '' ? $values['drive'] : null,
                $values['trim'] !== '' ? $values['trim'] : null,
                $values['options'] !== '' ? $values['options'] : null,
                $newId,
            ]);

            $pdo->commit();
            $saved = true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                $errors['general'] = 'Не удалось добавить: конфликт данных. Проверьте VIN и модель.';
            } else {
                $errors['general'] = 'Не удалось добавить автомобиль. Попробуйте ещё раз.';
            }
        }

        if ($saved && $newId > 0) {
            log_action('car.create', 'cars', $newId);
            flash('success', 'Автомобиль добавлен в реестр.');
            redirect(app_base() . 'admin/car_edit.php?id=' . $newId);
        }
    }
}

$flashSuccess = flash('success');

/* ---------- Вспомогательные значения ---------- */
$fmtMoney = static fn (float $v): string => number_format($v, 0, ',', ' ') . ' Br';

$imgUrl = static function (?string $path): string {
    $src = car_image_url($path);
    if (preg_match('~^(https?:)?//~i', $src)) {
        return $src;
    }
    return app_base() . ltrim($src, '/');
};

$previewModelName = $values['model_name'];

$previewMetaParts = [];
if ($values['body_type'] !== '') {
    $previewMetaParts[] = e((string) $values['body_type']);
}
if ($values['year'] !== '') {
    $previewMetaParts[] = e((string) $values['year']);
}
$previewMetaParts[] = $values['price'] !== '' ? e($fmtMoney((float) $values['price'])) : '—';
$previewMeta = implode(' · ', $previewMetaParts);

[$statusLabel, $statusClass] = $statusPills[$values['status']] ?? ['—', 'pill-canceled'];

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Добавление авто — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/custom-select.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <div class="admin-page-head">
            <div>
                <h1 class="admin-title">Добавление <span>· новый автомобиль</span></h1>
                <p class="admin-subtitle">Заполните данные автомобиля и комплектацию</p>
            </div>
            <a class="a-btn a-btn-ghost" href="cars.php">← К списку авто</a>
        </div>

        <?php if ($flashSuccess !== null): ?>
            <div class="a-alert a-alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>

        <div class="a-form-grid">
            <form class="a-form-card" id="carCreateForm" method="post" action="car_form.php">
                <?= csrf_field() ?>

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
                            <span class="a-field-hint">Марка фиксирована.</span>
                        </div>
                        <div class="a-field">
                            <label for="model_name">Модель</label>
                            <input type="text" id="model_name" name="model_name" class="a-input"
                                   list="modelList" maxlength="80"
                                   value="<?= e((string) $values['model_name']) ?>" required
                                   placeholder="Например, XC60">
                            <?php if (isset($errors['model_name'])): ?>
                                <span class="a-field-error"><?= e($errors['model_name']) ?></span>
                            <?php else: ?>
                                <span class="a-field-hint">Введите название модели — можно выбрать из подсказок.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="a-field">
                        <label for="body_type">Тип кузова</label>
                        <input type="text" id="body_type" name="body_type" class="a-input"
                               list="bodyTypeList" maxlength="40"
                               value="<?= e((string) $values['body_type']) ?>" required
                               placeholder="Например, SUV">
                        <?php if (isset($errors['body_type'])): ?>
                            <span class="a-field-error"><?= e($errors['body_type']) ?></span>
                        <?php else: ?>
                            <span class="a-field-hint">Тип кузова хранится у модели: для существующей модели он обновится для всех её авто.</span>
                        <?php endif; ?>
                    </div>

                    <div class="a-form-row">
                        <div class="a-field">
                            <label for="vin">VIN</label>
                            <input type="text" id="vin" name="vin" class="a-input" maxlength="17"
                                   value="<?= e((string) $values['vin']) ?>" required>
                            <?php if (isset($errors['vin'])): ?>
                                <span class="a-field-error"><?= e($errors['vin']) ?></span>
                            <?php else: ?>
                                <span class="a-field-hint">Ровно 17 символов, уникален.</span>
                            <?php endif; ?>
                        </div>
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
                            <label for="price">Цена, Br</label>
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

                <datalist id="modelList">
                    <?php foreach ($modelNames as $name): ?>
                        <option value="<?= e($name) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <datalist id="bodyTypeList">
                    <?php foreach ($bodyTypes as $type): ?>
                        <option value="<?= e($type) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <div class="a-form-actions">
                    <a class="a-btn a-btn-ghost" href="cars.php">Отмена</a>
                    <button type="submit" class="a-btn a-btn-primary">Добавить автомобиль</button>
                </div>
            </form>

            <aside class="a-side">
                <div class="a-preview">
                    <div class="a-preview-media">
                        <img id="previewImage" src="/" alt="">
                        <span class="pill <?= $statusClass ?> car-card-status" id="previewStatus"><?= e($statusLabel) ?></span>
                    </div>
                    <div class="a-preview-body">
                        <span class="a-preview-label">Предпросмотр</span>
                        <strong class="a-preview-name" id="previewName">
                            <?= $previewModelName !== '' ? 'Volvo ' . e($previewModelName) : 'Новый автомобиль' ?>
                        </strong>
                        <span class="a-preview-meta" id="previewMeta"><?= $previewMeta ?></span>
                    </div>
                </div>

                <div class="a-note">
                    <h3>Что учитывать</h3>
                    <p>Марка <strong>Volvo</strong> фиксирована. Модель и тип кузова вводятся вручную — новые модели создаются автоматически.</p>
                    <p>VIN должен быть уникальным и содержать ровно 17 символов.</p>
                    <p>Тип кузова хранится у модели: для существующей модели новое значение обновится у всех её авто.</p>
                    <p>Статус «Продан» устанавливается через оформление продажи, поэтому при добавлении недоступен.</p>
                </div>
            </aside>
        </div>

        <script>
            (function () {
                var form = document.getElementById('carCreateForm');
                if (!form) {
                    return;
                }

                var base = <?= json_encode(app_base(), JSON_UNESCAPED_SLASHES) ?>;

                var modelInput = document.getElementById('model_name');
                var bodyInput  = document.getElementById('body_type');
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
                    return n.toLocaleString('ru-RU', { maximumFractionDigits: 0 }) + ' Br';
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
                    var modelName = modelInput ? modelInput.value.trim() : '';
                    pName.textContent = modelName ? ('Volvo ' + modelName) : 'Новый автомобиль';

                    var metaParts = [];
                    if (bodyInput && bodyInput.value.trim() !== '') {
                        metaParts.push(bodyInput.value.trim());
                    }
                    if (yearInput && yearInput.value !== '') {
                        metaParts.push(yearInput.value);
                    }
                    metaParts.push(money(priceInput ? priceInput.value : ''));
                    pMeta.textContent = metaParts.join(' · ');

                    var st = statusMap[statusSel ? statusSel.value : ''] || ['—', 'pill-canceled'];
                    pStatus.textContent = st[0];
                    pStatus.className = 'pill ' + st[1] + ' car-card-status';

                    pImage.src = imgSrc(imageInput ? imageInput.value : '');
                }

                [modelInput, bodyInput, yearInput, priceInput, statusSel, imageInput].forEach(function (el) {
                    if (el) {
                        el.addEventListener('input', updatePreview);
                        el.addEventListener('change', updatePreview);
                    }
                });

                updatePreview();
            })();
        </script>

    </main>

    <script src="../assets/js/custom-select.js"></script>

</body>

</html>
