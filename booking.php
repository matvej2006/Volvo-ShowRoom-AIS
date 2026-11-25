<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

require_login();

$userId = (int) current_user()['id'];

$stmt = db()->query(
    'SELECT c.id, c.year, c.price, c.main_image, m.name AS model_name
       FROM cars c
       JOIN car_models m ON m.id = c.model_id
      WHERE c.status = \'available\'
   ORDER BY m.name, c.year DESC'
);
$cars = $stmt->fetchAll();

$errors = [];
$values = [
    'car_id'  => 0,
    'comment' => '',
];

$requestedCar = filter_input(INPUT_GET, 'car', FILTER_VALIDATE_INT);
if ($requestedCar) {
    $values['car_id'] = (int) $requestedCar;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $values['car_id']  = (int) ($_POST['car_id'] ?? 0);
    $values['comment'] = trim((string) ($_POST['comment'] ?? ''));

    $car = null;
    foreach ($cars as $item) {
        if ((int) $item['id'] === $values['car_id']) {
            $car = $item;
            break;
        }
    }
    if ($car === null) {
        $errors['car_id'] = 'Выберите автомобиль из списка доступных.';
    }

    if (mb_strlen($values['comment']) > 500) {
        $errors['comment'] = 'Комментарий не должен быть длиннее 500 символов.';
    }

    if ($car !== null) {
        $stmt = db()->prepare(
            'SELECT id FROM bookings
              WHERE user_id = ? AND car_id = ? AND status IN (\'new\', \'confirmed\')
              LIMIT 1'
        );
        $stmt->execute([$userId, (int) $car['id']]);
        if ($stmt->fetchColumn()) {
            $errors['car_id'] = 'У вас уже есть активное бронирование на этот автомобиль.';
        }

        $stmt = db()->prepare(
            'SELECT id FROM bookings
              WHERE car_id = ? AND status = \'confirmed\'
              LIMIT 1'
        );
        $stmt->execute([(int) $car['id']]);
        if ($stmt->fetchColumn()) {
            $errors['car_id'] = 'Этот автомобиль уже забронирован другим клиентом.';
        }
    }

    if ($errors === []) {
        $expiresAt = (new DateTimeImmutable())->modify('+48 hours');

        $stmt = db()->prepare(
            'INSERT INTO bookings (car_id, user_id, comment, status, expires_at)
             VALUES (?, ?, ?, \'new\', ?)'
        );
        $stmt->execute([
            (int) $car['id'],
            $userId,
            $values['comment'] !== '' ? $values['comment'] : null,
            $expiresAt->format('Y-m-d H:i:s'),
        ]);

        log_action('booking.create', 'bookings', (int) db()->lastInsertId());
        flash('success', 'Бронирование создано. Менеджер подтвердит его в ближайшее время.');
        redirect('profile.php');
    }
}

$previewCar = null;
foreach ($cars as $item) {
    if ((int) $item['id'] === $values['car_id']) {
        $previewCar = $item;
        break;
    }
}

$fmtMoney      = static fn($v): string => number_format((float) $v, 0, ',', ' ') . ' Br';
$flashSuccess  = flash('success');

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Бронирование — VolvoCarsBy</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require __DIR__ . '/includes/logged_header.php'; ?>

    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumbs" aria-label="Хлебные крошки">
                <a href="index.php">Главная</a>
                <span class="sep">/</span>
                <a href="index.php#models">Модели</a>
                <span class="sep">/</span>
                <span>Бронирование</span>
            </nav>

            <div class="section-header page-hero-head">
                <span class="section-label">Бронирование</span>
                <h1 class="section-title">Забронируйте <strong>автомобиль</strong></h1>
                <p class="section-subtitle">Выберите автомобиль — менеджер подтвердит бронь и свяжется с вами для оформления.</p>
            </div>

            <?php if ($flashSuccess !== null): ?>
                <div class="td-alert td-alert-success"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="td-alert td-alert-error">
                    Проверьте выделенные поля — бронирование пока не создано.
                </div>
            <?php endif; ?>

            <div class="td-grid">

                <div class="td-card">
                    <?php if ($cars === []): ?>
                        <div class="td-empty">
                            <h2>Свободных автомобилей нет</h2>
                            <p>Все машины сейчас забронированы или проданы. Позвоните нам — подскажем, когда появится нужная модель.</p>
                            <a href="contacts.php" class="btn btn-dark">Контакты салона</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="booking.php">
                            <?= csrf_field() ?>

                            <div class="field">
                                <label for="car_id">Автомобиль</label>
                                <select id="car_id" name="car_id" required>
                                    <option value="">— Выберите автомобиль —</option>
                                    <?php foreach ($cars as $item): ?>
                                        <option
                                            value="<?= (int) $item['id'] ?>"
                                            data-model="<?= e($item['model_name']) ?>"
                                            data-year="<?= e((string) $item['year']) ?>"
                                            data-price="<?= e($fmtMoney($item['price'])) ?>"
                                            data-image="<?= e(car_image_url($item['main_image'])) ?>"
                                            <?= (int) $item['id'] === $values['car_id'] ? 'selected' : '' ?>>
                                            Volvo <?= e($item['model_name']) ?>, <?= e($item['year']) ?> — <?= e($fmtMoney($item['price'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['car_id'])): ?>
                                    <span class="field-error"><?= e($errors['car_id']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="field">
                                <label for="comment">Комментарий</label>
                                <textarea
                                    id="comment"
                                    name="comment"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="Например: интересуюсь комплектацией R-Design, готов внести предоплату"><?= e($values['comment']) ?></textarea>
                                <?php if (isset($errors['comment'])): ?>
                                    <span class="field-error"><?= e($errors['comment']) ?></span>
                                <?php else: ?>
                                    <span class="field-hint">Необязательно, до 500 символов.</span>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-dark td-submit">Забронировать</button>

                            <p class="td-note">
                                Бронь действует 48 часов. За это время менеджер свяжется с вами для подтверждения.
                                После подтверждения автомобиль резервируется за вами до оформления сделки.
                            </p>
                        </form>
                    <?php endif; ?>
                </div>

                <aside class="td-side">

                    <div class="td-preview" id="tdPreview" <?= $previewCar === null ? 'hidden' : '' ?>>
                        <img
                            id="tdPreviewImage"
                            src="<?= e($previewCar !== null ? car_image_url($previewCar['main_image']) : 'assets/images/new_hero_xc60.jpg') ?>"
                            alt="Автомобиль для бронирования">
                        <div class="td-preview-body">
                            <span class="td-preview-label">Вы бронируете</span>
                            <strong class="td-preview-name" id="tdPreviewName">
                                <?= e($previewCar !== null ? 'Volvo ' . $previewCar['model_name'] : '') ?>
                            </strong>
                            <span class="td-preview-meta" id="tdPreviewMeta">
                                <?= $previewCar !== null ? e($previewCar['year']) . ' · ' . e($fmtMoney($previewCar['price'])) : '' ?>
                            </span>
                            <a class="td-preview-link" id="tdPreviewLink" href="<?= $previewCar !== null ? 'car.php?id=' . (int) $previewCar['id'] : '#' ?>">
                                Карточка автомобиля →
                            </a>
                        </div>
                    </div>

                    <div class="td-preview-empty" id="tdPreviewEmpty" <?= $previewCar !== null ? 'hidden' : '' ?>>
                        Выберите автомобиль — здесь появятся его фото и цена.
                    </div>

                    <div class="td-card td-steps">
                        <h2>Как работает бронирование</h2>
                        <ol>
                            <li>
                                <span class="step-num">1</span>
                                <div>
                                    <strong>Заявка</strong>
                                    <p>Выбираете автомобиль — бронь сразу появляется в личном кабинете.</p>
                                </div>
                            </li>
                            <li>
                                <span class="step-num">2</span>
                                <div>
                                    <strong>Подтверждение</strong>
                                    <p>Менеджер звонит и подтверждает бронь. Автомобиль резервируется за вами.</p>
                                </div>
                            </li>
                            <li>
                                <span class="step-num">3</span>
                                <div>
                                    <strong>Оформление</strong>
                                    <p>Подписываете договор, выбираете способ оплаты — и автомобиль ваш.</p>
                                </div>
                            </li>
                        </ol>
                        <a href="contacts.php" class="td-contacts-link">Контакты и как добраться →</a>
                    </div>

                </aside>
            </div>
        </div>
    </section>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script>
        const carSelect = document.getElementById('car_id');
        if (carSelect) {
            const preview = document.getElementById('tdPreview');
            const previewEmpty = document.getElementById('tdPreviewEmpty');
            const previewImage = document.getElementById('tdPreviewImage');
            const previewName = document.getElementById('tdPreviewName');
            const previewMeta = document.getElementById('tdPreviewMeta');
            const previewLink = document.getElementById('tdPreviewLink');

            const updatePreview = () => {
                const option = carSelect.options[carSelect.selectedIndex];

                if (!option || option.value === '') {
                    preview.hidden = true;
                    previewEmpty.hidden = false;
                    return;
                }

                previewImage.src = option.dataset.image;
                previewName.textContent = 'Volvo ' + option.dataset.model;
                previewMeta.textContent = option.dataset.year + ' · ' + option.dataset.price;
                previewLink.href = 'car.php?id=' + option.value;

                preview.hidden = false;
                previewEmpty.hidden = true;
            };

            carSelect.addEventListener('change', updatePreview);
            updatePreview();
        }
    </script>

</body>

</html>