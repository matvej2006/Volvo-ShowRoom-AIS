<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_admin();

$pdo = db();

/* ---------- Автомобили ---------- */
$carsByStatus = [];
foreach ($pdo->query("SELECT status, COUNT(*) AS c FROM cars GROUP BY status")->fetchAll() as $row) {
    $carsByStatus[$row['status']] = (int) $row['c'];
}
$totalCars     = array_sum($carsByStatus);
$availableCars = $carsByStatus['available'] ?? 0;

/* ---------- Пользователи ---------- */
$usersByRole = [];
foreach ($pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role")->fetchAll() as $row) {
    $usersByRole[$row['role']] = (int) $row['c'];
}
$totalUsers = array_sum($usersByRole);

/* ---------- Продажи ---------- */
$salesAgg = $pdo->query(
    'SELECT COUNT(*) AS c, COALESCE(SUM(price), 0) AS total FROM sales'
)->fetch();
$salesCount = (int) ($salesAgg['c'] ?? 0);
$salesTotal = (float) ($salesAgg['total'] ?? 0);

$monthSales = $pdo->query(
    "SELECT COUNT(*) AS c, COALESCE(SUM(price), 0) AS total
       FROM sales
      WHERE sold_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
)->fetch();
$monthSalesCount = (int) ($monthSales['c'] ?? 0);
$monthRevenue    = (float) ($monthSales['total'] ?? 0);

/* ---------- Заявки и брони ---------- */
$newLeads       = (int) $pdo->query("SELECT COUNT(*) FROM test_drives WHERE status = 'new'")->fetchColumn();
$totalLeads     = (int) $pdo->query('SELECT COUNT(*) FROM test_drives')->fetchColumn();
$activeBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('new', 'confirmed')")->fetchColumn();
$totalBookings  = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();

/* ---------- Последние продажи ---------- */
$recentSales = $pdo->query(
    'SELECT s.id, s.contract_number, s.price, s.payment_type, s.sold_at,
            m.name AS model_name, c.year, cl.name AS client_name
       FROM sales s
       JOIN cars c        ON c.id  = s.car_id
       JOIN car_models m  ON m.id  = c.model_id
       JOIN users cl      ON cl.id = s.client_id
   ORDER BY s.sold_at DESC, s.id DESC
      LIMIT 6'
)->fetchAll();

/* ---------- Журнал действий ---------- */
$recentActivity = $pdo->query(
    'SELECT al.action, al.entity, al.entity_id, al.created_at, u.name AS user_name
       FROM activity_log al
  LEFT JOIN users u ON u.id = al.user_id
   ORDER BY al.created_at DESC, al.id DESC
      LIMIT 12'
)->fetchAll();

/* ---------- Продажи по месяцам (6 мес.) ---------- */
$monthlyRows = $pdo->query(
    "SELECT DATE_FORMAT(sold_at, '%Y-%m') AS ym, COUNT(*) AS c, COALESCE(SUM(price), 0) AS total
       FROM sales
      WHERE sold_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 MONTH), '%Y-%m-01')
   GROUP BY ym
   ORDER BY ym"
)->fetchAll();

$byMonth = [];
foreach ($monthlyRows as $row) {
    $byMonth[$row['ym']] = ['count' => (int) $row['c'], 'total' => (float) $row['total']];
}

$monthNames = [1 => 'Янв', 2 => 'Фев', 3 => 'Мар', 4 => 'Апр', 5 => 'Май', 6 => 'Июн',
               7 => 'Июл', 8 => 'Авг', 9 => 'Сен', 10 => 'Окт', 11 => 'Ноя', 12 => 'Дек'];

$bars = [];
$cursor = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
for ($i = 0; $i < 6; $i++) {
    $d   = $cursor->modify('+' . $i . ' months');
    $key = $d->format('Y-m');
    $bars[] = [
        'label' => $monthNames[(int) $d->format('n')],
        'count' => $byMonth[$key]['count'] ?? 0,
        'total' => $byMonth[$key]['total'] ?? 0.0,
    ];
}
$maxCount      = max(1, max(array_column($bars, 'count')));
$barsHaveSales = array_sum(array_column($bars, 'count')) > 0;

/* ---------- Подписи ---------- */
$statusLabels = [
    'available' => ['В наличии', 'dot-available'],
    'reserved'  => ['Забронирован', 'dot-reserved'],
    'sold'      => ['Продан', 'dot-sold'],
    'service'   => ['На обслуживании', 'dot-service'],
];

$roleLabels = [
    'admin'   => ['Администратор', 'dot-admin'],
    'manager' => ['Менеджер', 'dot-manager'],
    'client'  => ['Клиент', 'dot-client'],
];

$paymentLabels = [
    'cash'    => 'Наличные',
    'credit'  => 'Кредит',
    'leasing' => 'Лизинг',
    'card'    => 'Карта',
];

$actionLabels = [
    'user.login'        => 'Вход',
    'user.logout'       => 'Выход',
    'user.register'     => 'Регистрация',
    'booking.create'    => 'Бронирование',
    'test_drive.create' => 'Тест-драйв',
];

$fmtMoney = static fn (float $v): string => number_format($v, 0, ',', ' ') . ' Br';
$fmtDate  = static fn (?string $dt): string => $dt ? date('d.m.Y H:i', strtotime($dt)) : '—';

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель администратора — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <div class="admin-page-head">
            <div>
                <h1 class="admin-title">Панель управления <span>· обзор сайта</span></h1>
                <p class="admin-subtitle">Ключевые показатели автосалона в реальном времени</p>
            </div>
            <div class="admin-date"><?= date('d.m.Y, H:i') ?></div>
        </div>

        <!-- KPI -->
        <section class="kpi-grid kpi-grid-6">
            <div class="kpi">
                <div class="kpi-label">В наличии</div>
                <div class="kpi-value"><?= $availableCars ?></div>
                <div class="kpi-sub">всего авто в базе: <?= $totalCars ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Продажи</div>
                <div class="kpi-value"><?= $salesCount ?></div>
                <div class="kpi-sub">за этот месяц: <?= $monthSalesCount ?></div>
            </div>
            <div class="kpi is-accent">
                <div class="kpi-label">Выручка</div>
                <div class="kpi-value"><?= e($fmtMoney($salesTotal)) ?></div>
                <div class="kpi-sub">за этот месяц: <?= e($fmtMoney($monthRevenue)) ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Новые заявки</div>
                <div class="kpi-value"><?= $newLeads ?></div>
                <div class="kpi-sub">всего заявок: <?= $totalLeads ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Активные брони</div>
                <div class="kpi-value"><?= $activeBookings ?></div>
                <div class="kpi-sub">всего броней: <?= $totalBookings ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Пользователи</div>
                <div class="kpi-value"><?= $totalUsers ?></div>
                <div class="kpi-sub">клиентов: <?= $usersByRole['client'] ?? 0 ?></div>
            </div>
        </section>

        <!-- Панели: прямые элементы сетки, ряды выравниваются по высоте -->
        <div class="admin-columns">

            <!-- 1. Продажи за 6 месяцев (широкая) -->
            <section class="panel">
                <div class="panel-head">
                    <h2>Продажи за 6 месяцев</h2>
                    <a href="../manager/sales.php">Все продажи →</a>
                </div>
                <div class="panel-body">
                    <?php if (!$barsHaveSales): ?>
                        <div class="empty">Пока нет данных о продажах</div>
                    <?php else: ?>
                        <div class="bars">
                            <?php foreach ($bars as $bar): ?>
                                <?php $width = (int) round($bar['count'] / $maxCount * 100); ?>
                                <div class="bar-row" title="<?= e($fmtMoney($bar['total'])) ?>">
                                    <span class="bar-label"><?= e($bar['label']) ?></span>
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: <?= $width ?>%"></div>
                                    </div>
                                    <span class="bar-value"><?= $bar['count'] ?> шт</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 2. Автомобили по статусам (узкая) -->
            <section class="panel">
                <div class="panel-head">
                    <h2>Автомобили по статусам</h2>
                    <a href="cars.php">Каталог →</a>
                </div>
                <div class="panel-body">
                    <ul class="stat-list">
                        <?php foreach ($statusLabels as $key => [$label, $dot]): ?>
                            <li>
                                <span class="lbl"><i class="dot <?= $dot ?>"></i><?= e($label) ?></span>
                                <span class="val"><?= $carsByStatus[$key] ?? 0 ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <!-- 3. Последние продажи (широкая) -->
            <section class="panel">
                <div class="panel-head">
                    <h2>Последние продажи</h2>
                    <a href="../manager/sales.php">Все →</a>
                </div>

                <?php if ($recentSales): ?>
                    <div style="overflow-x:auto">
                        <table class="a-table">
                            <thead>
                                <tr>
                                    <th>Договор</th>
                                    <th>Автомобиль</th>
                                    <th>Клиент</th>
                                    <th>Сумма</th>
                                    <th>Оплата</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $sale): ?>
                                    <tr>
                                        <td class="a-mono"><?= e($sale['contract_number']) ?></td>
                                        <td class="a-strong">Volvo <?= e($sale['model_name']) ?>, <?= e((string) $sale['year']) ?></td>
                                        <td><?= e($sale['client_name']) ?></td>
                                        <td class="a-mono a-strong"><?= e($fmtMoney((float) $sale['price'])) ?></td>
                                        <td class="a-muted"><?= e($paymentLabels[$sale['payment_type']] ?? $sale['payment_type']) ?></td>
                                        <td class="a-muted"><?= e($fmtDate($sale['sold_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty">Продаж пока нет</div>
                <?php endif; ?>
            </section>

            <!-- 4. Пользователи (узкая) -->
            <section class="panel">
                <div class="panel-head">
                    <h2>Пользователи</h2>
                    <a href="users.php">Сотрудники →</a>
                </div>
                <div class="panel-body">
                    <ul class="stat-list">
                        <?php foreach ($roleLabels as $key => [$label, $dot]): ?>
                            <li>
                                <span class="lbl"><i class="dot <?= $dot ?>"></i><?= e($label) ?></span>
                                <span class="val"><?= $usersByRole[$key] ?? 0 ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

        </div>

        <!-- Быстрые действия (полная ширина) -->
        <section class="panel">
            <div class="panel-head">
                <h2>Быстрые действия</h2>
            </div>
            <div class="panel-body">
                <div class="quick-grid">
                    <a class="quick" href="car_form.php">Добавить авто <span>→</span></a>
                    <a class="quick" href="cars.php">Автомобили <span>→</span></a>
                    <a class="quick" href="users.php">Сотрудники <span>→</span></a>
                    <a class="quick" href="../manager/leads.php">Заявки <span>→</span></a>
                    <a class="quick" href="../manager/sales.php">Продажи <span>→</span></a>
                    <a class="quick" href="../manager/clients.php">Клиенты <span>→</span></a>
                </div>
            </div>
        </section>

        <!-- Журнал действий (полная ширина) -->
        <section class="panel">
            <div class="panel-head">
                <h2>Последние действия сотрудников</h2>
            </div>

            <?php if ($recentActivity): ?>
                <div style="overflow-x:auto">
                    <table class="a-table">
                        <thead>
                            <tr>
                                <th>Время</th>
                                <th>Пользователь</th>
                                <th>Действие</th>
                                <th>Объект</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $log): ?>
                                <tr>
                                    <td class="a-muted a-mono"><?= e($fmtDate($log['created_at'])) ?></td>
                                    <td><?= e($log['user_name'] ?? 'Гость') ?></td>
                                    <td><?= e($actionLabels[$log['action']] ?? $log['action']) ?></td>
                                    <td class="a-muted">
                                        <?php if (!empty($log['entity'])): ?>
                                            <?= e($log['entity']) ?><?= $log['entity_id'] !== null ? ' #' . (int) $log['entity_id'] : '' ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty">Журнал действий пуст</div>
            <?php endif; ?>
        </section>

    </main>

</body>

</html>
