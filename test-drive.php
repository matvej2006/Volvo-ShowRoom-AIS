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
    'car_id'         => 0,
    'preferred_date' => '',
    'comment'        => '',
];

$requestedCar = filter_input(INPUT_GET, 'car', FILTER_VALIDATE_INT);
if ($requestedCar) {
    $values['car_id'] = (int) $requestedCar;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $values['car_id']         = (int) ($_POST['car_id'] ?? 0);
    $values['preferred_date'] = trim((string) ($_POST['preferred_date'] ?? ''));
    $values['comment']        = trim((string) ($_POST['comment'] ?? ''));

    // Автомобиль должен быть из списка доступных.
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

    // Дата и время визита.
    $date = null;
    if ($values['preferred_date'] === '') {
        $errors['preferred_date'] = 'Укажите желаемые дату и время.';
    } else {
        $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $values['preferred_date']);
        $now  = new DateTimeImmutable();

        if (!$date || $date->format('Y-m-d\TH:i') !== $values['preferred_date']) {
            $errors['preferred_date'] = 'Некорректная дата или время.';
            $date = null;
        } elseif ($date <= $now->modify('+30 minutes')) {
            $errors['preferred_date'] = 'Выберите время не раньше чем через 30 минут.';
        } elseif ($date > $now->modify('+90 days')) {
            $errors['preferred_date'] = 'Запись открыта не более чем на 90 дней вперёд.';
        } elseif ((int) $date->format('G') < 9 || (int) $date->format('G') >= 21) {
            $errors['preferred_date'] = 'Салон принимает с 9:00 до 21:00.';
        }
    }

    if (mb_strlen($values['comment']) > 500) {
        $errors['comment'] = 'Комментарий не должен быть длиннее 500 символов.';
    }

    // На один автомобиль — одна активная заявка.
    if ($car !== null) {
        $stmt = db()->prepare(
            'SELECT id FROM test_drives
              WHERE user_id = ? AND car_id = ? AND status IN (\'new\', \'in_progress\')
              LIMIT 1'
        );
        $stmt->execute([$userId, (int) $car['id']]);
        if ($stmt->fetchColumn()) {
            $errors['car_id'] = 'У вас уже есть активная заявка на этот автомобиль.';
        }
    }

    if ($errors === []) {
        $stmt = db()->prepare(
            'INSERT INTO test_drives (car_id, user_id, preferred_date, comment, status)
             VALUES (?, ?, ?, ?, \'new\')'
        );
        $stmt->execute([
            (int) $car['id'],
            $userId,
            $date->format('Y-m-d H:i:s'),
            $values['comment'] !== '' ? $values['comment'] : null,
        ]);

        log_action('test_drive.create', 'test_drives', (int) db()->lastInsertId());
        flash('success', 'Заявка на тест-драйв создана. Менеджер свяжется с вами для подтверждения времени.');
        redirect('profile.php');
    }
}

// Автомобиль для блока предпросмотра (только если выбран реально существующий).
$previewCar = null;
foreach ($cars as $item) {
    if ((int) $item['id'] === $values['car_id']) {
        $previewCar = $item;
        break;
    }
}

$fmtMoney = static fn ($value): string => number_format((float) $value, 0, ',', ' ') . ' Br';
$flashSuccess = flash('success');

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Тест-драйв — VolvoCarsBy</title>
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
                <span>Тест-драйв</span>
            </nav>

            <div class="section-header page-hero-head">
                <span class="section-label">Запись на визит</span>
                <h1 class="section-title">Запишитесь на <strong>тест-драйв</strong></h1>
                <p class="section-subtitle">Выберите автомобиль и удобное время — менеджер подтвердит запись и подготовит машину к вашему приезду.</p>
            </div>

            <?php if ($flashSuccess !== null): ?>
                <div class="td-alert td-alert-success"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="td-alert td-alert-error">
                    Проверьте выделенные поля — заявка пока не отправлена.
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
                        <form method="POST" action="test-drive.php">
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
                                <label for="preferred_date">Дата и время визита</label>
                                <input
                                    type="datetime-local"
                                    id="preferred_date"
                                    name="preferred_date"
                                    value="<?= e($values['preferred_date']) ?>"
                                    min="<?= e(date('Y-m-d\TH:i')) ?>"
                                    max="<?= e(date('Y-m-d\TH:i', strtotime('+90 days'))) ?>"
                                    required>
                                <?php if (isset($errors['preferred_date'])): ?>
                                    <span class="field-error"><?= e($errors['preferred_date']) ?></span>
                                <?php else: ?>
                                    <span class="field-hint">Салон работает ежедневно с 9:00 до 21:00.</span>
                                <?php endif; ?>
                            </div>

                            <div class="field">
                                <label for="comment">Комментарий</label>
                                <textarea
                                    id="comment"
                                    name="comment"
                                    rows="4"
                                    maxlength="500"
                                    placeholder="Например: интересуюсь гибридной версией, хочу сравнить с XC90"><?= e($values['comment']) ?></textarea>
                                <?php if (isset($errors['comment'])): ?>
                                    <span class="field-error"><?= e($errors['comment']) ?></span>
                                <?php else: ?>
                                    <span class="field-hint">Необязательно, до 500 символов.</span>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-dark td-submit">Отправить заявку</button>

                            <p class="td-note">
                                Заявка не бронирует автомобиль. С собой нужны паспорт и водительское удостоверение,
                                стаж вождения — от 2 лет.
                            </p>
                        </form>
                    <?php endif; ?>
                </div>

                <aside class="td-side">

                    <div class="td-preview" id="tdPreview" <?= $previewCar === null ? 'hidden' : '' ?>>
                        <img
                            id="tdPreviewImage"
                            src="<?= e($previewCar !== null ? car_image_url($previewCar['main_image']) : 'assets/images/new_hero_xc60.jpg') ?>"
                            alt="Автомобиль для тест-драйва">
                        <div class="td-preview-body">
                            <span class="td-preview-label">Вы записываетесь на</span>
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
                        <h2>Как проходит запись</h2>
                        <ol>
                            <li>
                                <span class="step-num">1</span>
                                <div>
                                    <strong>Заявка</strong>
                                    <p>Выбираете автомобиль и время — заявка сразу появляется в личном кабинете.</p>
                                </div>
                            </li>
                            <li>
                                <span class="step-num">2</span>
                                <div>
                                    <strong>Подтверждение</strong>
                                    <p>Менеджер звонит в течение рабочего дня и согласует слот.</p>
                                </div>
                            </li>
                            <li>
                                <span class="step-num">3</span>
                                <div>
                                    <strong>Визит</strong>
                                    <p>30–40 минут за рулём по городскому маршруту, затем обсуждение комплектации.</p>
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
