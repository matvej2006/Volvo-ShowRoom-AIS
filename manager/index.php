<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_manager();

$pdo = db();

$newLeads       = (int) $pdo->query("SELECT COUNT(*) FROM test_drives WHERE status = 'new'")->fetchColumn();
$activeBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('new', 'confirmed')")->fetchColumn();
$monthSales     = $pdo->query(
    "SELECT COUNT(*) AS c, COALESCE(SUM(price), 0) AS total
       FROM sales
      WHERE sold_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
)->fetch();
$monthSalesCount = (int) ($monthSales['c'] ?? 0);
$monthRevenue    = (float) ($monthSales['total'] ?? 0);
$clientsCount    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

$recentLeads = $pdo->query(
    "SELECT td.id, td.preferred_date, td.status, td.created_at,
            m.name AS model_name, c.year, u.name AS user_name
       FROM test_drives td
       JOIN cars c       ON c.id = td.car_id
       JOIN car_models m ON m.id = c.model_id
  LEFT JOIN users u      ON u.id = td.user_id
   ORDER BY td.created_at DESC, td.id DESC
      LIMIT 6"
)->fetchAll();

$recentBookings = $pdo->query(
    "SELECT b.id, b.status, b.expires_at, b.created_at,
            m.name AS model_name, c.year, c.price, u.name AS user_name
       FROM bookings b
       JOIN cars c       ON c.id = b.car_id
       JOIN car_models m ON m.id = c.model_id
  LEFT JOIN users u      ON u.id = b.user_id
   ORDER BY b.created_at DESC, b.id DESC
      LIMIT 6"
)->fetchAll();

$leadStatuses = [
    'new'         => ['Ожидает', 'pill-new'],
    'in_progress' => ['В работе', 'pill-progress'],
    'done'        => ['Проведён', 'pill-done'],
    'canceled'    => ['Отменён', 'pill-canceled'],
];

$bookingStatuses = [
    'new'       => ['Новая', 'pill-new'],
    'confirmed' => ['Подтверждена', 'pill-confirmed'],
    'canceled'  => ['Отменена', 'pill-canceled'],
    'expired'   => ['Истекла', 'pill-expired'],
];

$fmtMoney = static fn (float $v): string => number_format($v, 0, ',', ' ') . ' ₽';
$fmtDate  = static fn (?string $dt): string => $dt ? date('d.m.Y H:i', strtotime($dt)) : '—';

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Панель менеджера — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <div class="admin-page-head">
            <div>
                <h1 class="admin-title">Панель управления <span>· менеджер</span></h1>
                <p class="admin-subtitle">Заявки, брони и продажи салона</p>
            </div>
            <div class="admin-date"><?= date('d.m.Y, H:i') ?></div>
        </div>

        <section class="kpi-grid">
            <div class="kpi">
                <div class="kpi-label">Новые заявки</div>
                <div class="kpi-value"><?= $newLeads ?></div>
                <div class="kpi-sub">на тест-драйв</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Активные брони</div>
                <div class="kpi-value"><?= $activeBookings ?></div>
                <div class="kpi-sub">новые и подтверждённые</div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Продажи за месяц</div>
                <div class="kpi-value"><?= $monthSalesCount ?></div>
                <div class="kpi-sub"><?= e($fmtMoney($monthRevenue)) ?></div>
            </div>
            <div class="kpi">
                <div class="kpi-label">Клиенты</div>
                <div class="kpi-value"><?= $clientsCount ?></div>
                <div class="kpi-sub">зарегистрировано</div>
            </div>
        </section>

        <div class="admin-columns">
            <section class="panel">
                <div class="panel-head">
                    <h2>Последние заявки на тест-драйв</h2>
                    <a href="leads.php">Все заявки →</a>
                </div>
                <?php if ($recentLeads): ?>
                    <div style="overflow-x:auto">
                        <table class="a-table">
                            <thead>
                                <tr>
                                    <th>Автомобиль</th>
                                    <th>Клиент</th>
                                    <th>Дата визита</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLeads as $lead): ?>
                                    <?php [$text, $cls] = $leadStatuses[$lead['status']] ?? [$lead['status'], 'pill-canceled']; ?>
                                    <tr>
                                        <td class="a-strong">Volvo <?= e($lead['model_name']) ?>, <?= e((string) $lead['year']) ?></td>
                                        <td><?= e($lead['user_name'] ?? 'Гость') ?></td>
                                        <td class="a-muted"><?= e($fmtDate($lead['preferred_date'])) ?></td>
                                        <td><span class="pill <?= $cls ?>"><?= e($text) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty">Заявок пока нет</div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-head">
                    <h2>Последние брони</h2>
                    <a href="sales.php">Продажи →</a>
                </div>
                <?php if ($recentBookings): ?>
                    <div style="overflow-x:auto">
                        <table class="a-table">
                            <thead>
                                <tr>
                                    <th>Автомобиль</th>
                                    <th>Клиент</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <?php [$text, $cls] = $bookingStatuses[$booking['status']] ?? [$booking['status'], 'pill-canceled']; ?>
                                    <tr>
                                        <td class="a-strong">Volvo <?= e($booking['model_name']) ?>, <?= e((string) $booking['year']) ?></td>
                                        <td><?= e($booking['user_name'] ?? 'Гость') ?></td>
                                        <td><span class="pill <?= $cls ?>"><?= e($text) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty">Броней пока нет</div>
                <?php endif; ?>
            </section>
        </div>
        <section class="panel">
            <div class="panel-head">
                <h2>Быстрые действия</h2>
            </div>
            <div class="panel-body">
                <div class="quick-grid-manager">
                    <a class="quick" href="../manager/leads.php">Заявки <span>→</span></a>
                    <a class="quick" href="../manager/sales.php">Продажи <span>→</span></a>
                    <a class="quick" href="../manager/clients.php">Клиенты <span>→</span></a>
                </div>
            </div>
        </section>
    </main>

</body>

</html>
