<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$contacts = [
    [
        'label'   => 'Адрес салона',
        'value'   => 'г. Москва, ул. Примерная, 1',
        'hint'    => 'Бесплатная парковка для клиентов на территории салона',
        'link'    => null,
        'icon'    => '<path d="M12 21s7-6.3 7-11a7 7 0 1 0-14 0c0 4.7 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
    ],
    [
        'label'   => 'Телефон',
        'value'   => '+375 (29) 123-45-67',
        'hint'    => 'Отдел продаж и запись на тест-драйв',
        'link'    => 'tel:+375291234567',
        'icon'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/>',
    ],
    [
        'label'   => 'Почта',
        'value'   => 'info@volvo-car-mensk.com',
        'hint'    => 'Ответим в течение рабочего дня',
        'link'    => 'mailto:info@volvo-car-mensk.com',
        'icon'    => '<rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6 9 6.5L21 6"/>',
    ],
    [
        'label'   => 'Часы работы',
        'value'   => 'Пн-Вс: 9:00 — 21:00',
        'hint'    => 'Тест-драйв возможен в любой день по записи',
        'link'    => null,
        'icon'    => '<circle cx="12" cy="12" r="9.5"/><path d="M12 6.5V12l4 2.5"/>',
    ],
];

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Контакты — VolvoCarsBy</title>
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
                <span>Контакты</span>
            </nav>

            <div class="section-header page-hero-head">
                <span class="section-label">Свяжитесь с нами</span>
                <h1 class="section-title">Контакты <strong>VolvoCarsBy</strong></h1>
                <p class="section-subtitle">Приезжайте в салон, позвоните или напишите — ответим на вопросы о моделях, наличии, кредите и сервисе.</p>
            </div>

            <div class="contacts-grid">
                <?php foreach ($contacts as $item): ?>
                    <div class="contact-card">
                        <span class="contact-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <?= $item['icon'] ?>
                            </svg>
                        </span>
                        <span class="contact-label"><?= e($item['label']) ?></span>
                        <?php if ($item['link'] !== null): ?>
                            <a class="contact-value" href="<?= e($item['link']) ?>"><?= e($item['value']) ?></a>
                        <?php else: ?>
                            <span class="contact-value"><?= e($item['value']) ?></span>
                        <?php endif; ?>
                        <span class="contact-hint"><?= e($item['hint']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="contacts-map">
                <div class="contacts-map-info">
                    <h2>Как нас найти</h2>
                    <ul class="contacts-steps">
                        <li>На автомобиле — заезд с улицы Примерной, парковка перед салоном бесплатная.</li>
                        <li>На общественном транспорте — удобнее всего от ближайшей станции метро, далее 5–7 минут пешком.</li>
                        <li>На входе назовите менеджеру номер заявки или имя — вас встретят в зоне приёма.</li>
                    </ul>
                    <div class="contacts-map-actions">
                        <a href="test-drive.php" class="btn btn-dark">Записаться на тест-драйв</a>
                        <a href="index.php#models" class="btn btn-outline-dark">Смотреть модели</a>
                    </div>
                </div>

                <div class="map-frame">
                    <iframe
                        title="Карта — VolvoCarsBy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox=37.594%2C55.744%2C37.634%2C55.764&amp;layer=mapnik"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            <div class="contacts-cta">
                <div>
                    <h2>Остались вопросы?</h2>
                    <p>Позвоните нам или напишите — подскажем по комплектациям, срокам поставки и условиям покупки.</p>
                </div>
                <div class="contacts-cta-actions">
                    <a href="tel:+375291234567" class="btn btn-primary">+375 (29) 123-45-67</a>
                    <a href="mailto:info@volvo-car-mensk.com" class="btn btn-outline">Написать на почту</a>
                </div>
            </div>

        </div>
    </section>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script>
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 50);
        });

        const mobileToggle = document.getElementById('mobileToggle');
        const navMenu = document.getElementById('navMenu');
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    </script>

</body>

</html>
