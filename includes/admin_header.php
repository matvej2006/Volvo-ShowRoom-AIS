<?php
/**
 * Общая шапка панели администратора и менеджера — в стиле основной шапки сайта.
 * Ожидает, что config/auth.php уже подключён (current_user(), e()).
 * $base — префикс пути к корню сайта (по умолчанию '../' для страниц в /admin и /manager).
 */

$base = $base ?? '../';
$role = current_user()['role'] ?? 'client';
$isAdminPanel = $role === 'admin';

$roleTitles = [
    'admin'   => 'Администратор',
    'manager' => 'Менеджер',
    'client'  => 'Клиент',
];
?>
<header class="admin-header" id="adminHeader">
    <div class="admin-header-inner">
        <a class="admin-brand" href="/Volvo_Showroom/index.php">
            <img src="<?= $base ?>assets/images/volvo_new_logo-freelogovectors.net_.png"
                 alt="Volvo" class="admin-brand-logo">
        </a>

        <nav class="admin-nav" aria-label="Навигация панели">
            <a href="<?= $base ?><?= $isAdminPanel ? 'admin/index.php' : 'manager/index.php' ?>">Дашборд</a>

            <?php if ($isAdminPanel): ?>
                <a href="<?= $base ?>admin/cars.php">Автомобили</a>
            <?php endif; ?>

            <a href="<?= $base ?>manager/leads.php">Заявки</a>
            <a href="<?= $base ?>manager/sales.php">Продажи</a>
            <?php if (is_admin()): ?>
                <a href="<?= $base ?>admin/users.php">Все пользователи</a>
            <?php elseif (is_manager()): ?>
                <a href="<?= $base ?>manager/clients.php">Клиенты</a>
            <?php endif; ?>
        </nav>

        <div class="admin-user">
            <span class="admin-user-name"><?= e(current_user()['name'] ?? '') ?></span>
            <span class="admin-user-role"><?= e($roleTitles[$role] ?? $role) ?></span>
            <a class="admin-btn" href="<?= $base ?>index.php">На сайт</a>
            <a class="admin-btn" href="<?= $base ?>logout.php">Выйти</a>
        </div>
    </div>
</header>
