<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/db.php';

require_login();

$userId = (int) current_user()['id'];

$stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch() ?: current_user();

$stmt = db()->prepare(
    'SELECT td.id, td.preferred_date, td.status, td.comment, td.created_at,
            m.name AS model_name, c.year
       FROM test_drives td
       JOIN cars c ON c.id = td.car_id
       JOIN car_models m ON m.id = c.model_id
      WHERE td.user_id = ?
   ORDER BY td.created_at DESC'
);
$stmt->execute([$userId]);
$testDrives = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT b.id, b.status, b.expires_at, b.created_at,
            m.name AS model_name, c.year, c.price
       FROM bookings b
       JOIN cars c ON c.id = b.car_id
       JOIN car_models m ON m.id = c.model_id
      WHERE b.user_id = ?
   ORDER BY b.created_at DESC'
);
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT s.id, s.contract_number, s.price, s.payment_type, s.sold_at,
            m.name AS model_name, c.year
       FROM sales s
       JOIN cars c ON c.id = s.car_id
       JOIN car_models m ON m.id = c.model_id
      WHERE s.client_id = ?
   ORDER BY s.sold_at DESC'
);
$stmt->execute([$userId]);
$sales = $stmt->fetchAll();

$words    = preg_split('/\s+/u', trim((string) $user['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= mb_strtoupper(mb_substr($word, 0, 1));
}
if ($initials === '') {
    $initials = 'V';
}

$roleLabelMap = [
    'admin'   => 'Администратор',
    'manager' => 'Менеджер',
    'client'  => 'Клиент',
];

$testDriveStatuses = [
    'new'         => ['Ожидает', 'pill-amber'],
    'in_progress' => ['В работе', 'pill-blue'],
    'done'        => ['Проведён', 'pill-green'],
    'canceled'    => ['Отменён', 'pill-muted'],
];

$bookingStatuses = [
    'new'       => ['Новая', 'pill-amber'],
    'confirmed' => ['Подтверждена', 'pill-green'],
    'canceled'  => ['Отменена', 'pill-muted'],
    'expired'   => ['Истекла', 'pill-muted'],
];

$paymentLabels = [
    'cash'    => 'Наличные',
    'credit'  => 'Кредит',
    'leasing' => 'Лизинг',
    'card'    => 'Карта',
];

$fmtDate     = static fn (?string $dt): string => $dt ? date('d.m.Y', strtotime($dt)) : '—';
$fmtDateTime = static fn (?string $dt): string => $dt ? date('d.m.Y H:i', strtotime($dt)) : '—';
$fmtMoney    = static fn ($v): string => number_format((float) $v, 0, ',', ' ') . ' ₽';

$flashSuccess = flash('success');
$flashError   = flash('error');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Личный кабинет — VolvoCarsBy</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --cabinet-bg: #0c0c0f;
            --cabinet-surface: rgba(255, 255, 255, 0.04);
            --cabinet-border: rgba(255, 255, 255, 0.06);
            --cabinet-border-active: rgba(255, 255, 255, 0.12);
        }

        .cabinet-page {
            min-height: 100vh;
            padding: 140px 0 80px;
            background: linear-gradient(170deg, #0a0a0e 0%, #0f0f14 40%, #0c0c11 100%);
            position: relative;
            overflow: hidden;
        }

        .cabinet-page::before {
            content: "";
            position: absolute;
            top: -30%;
            left: 20%;
            width: 700px;
            height: 700px;
            background: radial-gradient(circle, rgba(74, 109, 140, 0.07) 0%, transparent 70%);
            pointer-events: none;
        }

        .cabinet-page::after {
            content: "";
            position: absolute;
            bottom: -20%;
            right: 10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(74, 109, 140, 0.04) 0%, transparent 70%);
            pointer-events: none;
        }

        .cabinet-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 28px;
            align-items: start;
            position: relative;
            z-index: 1;
        }

        /* ===== Sidebar ===== */
        .cabinet-sidebar {
            position: sticky;
            background: rgba(255, 255, 255, 0.025);
            backdrop-filter: blur(14px) saturate(140%);
            -webkit-backdrop-filter: blur(14px) saturate(140%);
            border: 1px solid var(--cabinet-border);
            border-radius: 20px;
            padding: 32px 24px 24px;
            text-align: center;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.15);
        }

        .cabinet-sidebar::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            border-radius: 20px 20px 0 0;
        }

        .cabinet-avatar {
            width: 68px;
            height: 68px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1a1a2e 0%, #3a4a60 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1px;
            box-shadow:
                0 4px 20px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .cabinet-name {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 3px;
        }

        .cabinet-role {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 980px;
            margin-bottom: 20px;
        }

        .role-admin {
            background: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.18);
        }

        .role-manager {
            background: rgba(96, 165, 250, 0.12);
            color: #93c5fd;
            border: 1px solid rgba(96, 165, 250, 0.18);
        }

        .role-client {
            background: rgba(52, 211, 153, 0.12);
            color: #6ee7b7;
            border: 1px solid rgba(52, 211, 153, 0.18);
        }

        .cabinet-meta {
            text-align: left;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 20px;
            margin-bottom: 20px;
        }

        .cabinet-meta li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .cabinet-meta li:last-child {
            border-bottom: none;
        }

        .cabinet-meta .meta-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.35);
            letter-spacing: 0.5px;
        }

        .cabinet-meta .meta-value {
            font-size: 12px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.75);
            text-align: right;
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cabinet-logout {
            width: 100%;
            padding: 11px 20px !important;
            font-size: 12px !important;
            background: rgba(255, 255, 255, 0.04) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: rgba(255, 255, 255, 0.65) !important;
        }

        .cabinet-logout:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #fff !important;
        }

        /* ===== Main ===== */
        .cabinet-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ===== Alerts ===== */
        .cabinet-alert {
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
        }

        .cabinet-alert-success {
            background: rgba(52, 211, 153, 0.08);
            color: #6ee7b7;
            border: 1px solid rgba(52, 211, 153, 0.15);
        }

        .cabinet-alert-error {
            background: rgba(248, 113, 113, 0.08);
            color: #fca5a5;
            border: 1px solid rgba(248, 113, 113, 0.15);
        }

        /* ===== Stats ===== */
        .cabinet-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .cabinet-stat {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px) saturate(140%);
            -webkit-backdrop-filter: blur(12px) saturate(140%);
            border: 1px solid var(--cabinet-border);
            border-radius: 18px;
            padding: 24px 20px;
            text-align: center;
            transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .cabinet-stat::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.06), transparent);
        }

        .cabinet-stat:hover {
            border-color: var(--cabinet-border-active);
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.25);
        }

        .cabinet-stat .stat-num {
            display: block;
            font-size: 38px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, rgba(255, 255, 255, 0.6) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 6px;
        }

        .cabinet-stat .stat-label {
            font-size: 11px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.35);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* ===== Panels ===== */
        .cabinet-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px) saturate(140%);
            -webkit-backdrop-filter: blur(12px) saturate(140%);
            border: 1px solid var(--cabinet-border);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.15);
        }

        .cabinet-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .cabinet-panel-head h3 {
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .cabinet-panel-head .panel-badge {
            font-size: 11px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.35);
            padding: 4px 10px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 980px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* ===== Table ===== */
        .cabinet-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .cabinet-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cabinet-table thead th {
            font-size: 10px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 14px 24px;
            text-align: left;
            background: rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .cabinet-table tbody td {
            padding: 16px 24px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            white-space: nowrap;
        }

        .cabinet-table tbody tr:last-child td {
            border-bottom: none;
        }

        .cabinet-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .cell-title {
            color: #fff;
            font-weight: 500;
        }

        .cell-sub {
            display: block;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.3);
            margin-top: 1px;
        }

        /* ===== Pills ===== */
        .pill {
            display: inline-block;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 980px;
            white-space: nowrap;
        }

        .pill-amber {
            background: rgba(251, 191, 36, 0.12);
            color: #fcd34d;
            border: 1px solid rgba(251, 191, 36, 0.18);
        }

        .pill-blue {
            background: rgba(96, 165, 250, 0.12);
            color: #93c5fd;
            border: 1px solid rgba(96, 165, 250, 0.18);
        }

        .pill-green {
            background: rgba(52, 211, 153, 0.12);
            color: #6ee7b7;
            border: 1px solid rgba(52, 211, 153, 0.18);
        }

        .pill-muted {
            background: rgba(255, 255, 255, 0.04);
            color: rgba(255, 255, 255, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* ===== Empty ===== */
        .cabinet-empty {
            padding: 48px 24px;
            text-align: center;
            color: rgba(255, 255, 255, 0.25);
            font-size: 13px;
        }

        .cabinet-empty svg {
            display: block;
            margin: 0 auto 16px;
            opacity: 0.4;
        }

        .cabinet-empty a {
            color: rgba(255, 255, 255, 0.5);
            font-weight: 600;
            transition: color 0.3s;
        }

        .cabinet-empty a:hover {
            color: #fff;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1024px) {
            .cabinet-grid {
                grid-template-columns: 1fr;
            }

            .cabinet-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .cabinet-page {
                padding: 110px 0 60px;
            }

            .cabinet-sidebar {
                padding: 28px 20px;
                border-radius: 18px;
            }

            .cabinet-stats {
                grid-template-columns: repeat(3, 1fr);
            }

            .cabinet-stat {
                padding: 18px 10px;
                border-radius: 14px;
            }

            .cabinet-stat .stat-num {
                font-size: 26px;
            }

            .cabinet-panel {
                border-radius: 16px;
            }

            .cabinet-panel-head {
                padding: 16px 18px;
            }

            .cabinet-table thead th,
            .cabinet-table tbody td {
                padding: 12px 16px;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <?php require 'includes/logged-header.php'; ?>

    <section class="cabinet-page">
        <div class="container">

            <?php if ($flashSuccess !== null): ?>
                <div class="cabinet-alert cabinet-alert-success" style="margin-bottom:24px"><?= e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError !== null): ?>
                <div class="cabinet-alert cabinet-alert-error" style="margin-bottom:24px"><?= e($flashError) ?></div>
            <?php endif; ?>

            <div class="cabinet-grid">

                <!-- Sidebar -->
                <aside class="cabinet-sidebar">
                    <div class="cabinet-avatar"><?= e($initials) ?></div>
                    <h2 class="cabinet-name"><?= e($user['name']) ?></h2>
                    <span class="cabinet-role role-<?= e($user['role'] ?? 'client') ?>">
                        <?= e($roleLabelMap[$user['role'] ?? ''] ?? 'Клиент') ?>
                    </span>

                    <ul class="cabinet-meta">
                        <li>
                            <span class="meta-label">Email</span>
                            <span class="meta-value"><?= e($user['email']) ?></span>
                        </li>
                        <li>
                            <span class="meta-label">Телефон</span>
                            <span class="meta-value"><?= !empty($user['phone']) ? e($user['phone']) : '—' ?></span>
                        </li>
                        <li>
                            <span class="meta-label">Клиент с</span>
                            <span class="meta-value"><?= e($fmtDate($user['created_at'] ?? null)) ?></span>
                        </li>
                    </ul>

                    <button type="button" onclick="location.href='logout.php'" class="btn btn-outline cabinet-logout">
                        Выйти из аккаунта
                    </button>
                </aside>

                <!-- Main content -->
                <div class="cabinet-main">

                    <!-- Stats -->
                    <div class="cabinet-stats">
                        <div class="cabinet-stat">
                            <span class="stat-num"><?= count($testDrives) ?></span>
                            <span class="stat-label">Тест-драйвы</span>
                        </div>
                        <div class="cabinet-stat">
                            <span class="stat-num"><?= count($bookings) ?></span>
                            <span class="stat-label">Брони</span>
                        </div>
                        <div class="cabinet-stat">
                            <span class="stat-num"><?= count($sales) ?></span>
                            <span class="stat-label">Покупки</span>
                        </div>
                    </div>

                    <!-- Test drives -->
                    <div class="cabinet-panel">
                        <div class="cabinet-panel-head">
                            <h3>Заявки на тест-драйв</h3>
                            <span class="panel-badge"><?= count($testDrives) ?></span>
                        </div>

                        <?php if ($testDrives): ?>
                            <div class="cabinet-table-wrap">
                                <table class="cabinet-table">
                                    <thead>
                                        <tr>
                                            <th>Автомобиль</th>
                                            <th>Желаемая дата</th>
                                            <th>Статус</th>
                                            <th>Создана</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($testDrives as $td): ?>
                                            <?php [$sText, $sClass] = $testDriveStatuses[$td['status']] ?? [$td['status'], 'pill-muted']; ?>
                                            <tr>
                                                <td>
                                                    <span class="cell-title">Volvo <?= e($td['model_name']) ?></span>
                                                    <span class="cell-sub"><?= e($td['year']) ?> г.</span>
                                                </td>
                                                <td><?= e($fmtDateTime($td['preferred_date'])) ?></td>
                                                <td><span class="pill <?= e($sClass) ?>"><?= e($sText) ?></span></td>
                                                <td><?= e($fmtDate($td['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="cabinet-empty">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>
                                </svg>
                                <p>Нет заявок на тест-драйв. <a href="index.php#models">Выбрать модель →</a></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bookings -->
                    <div class="cabinet-panel">
                        <div class="cabinet-panel-head">
                            <h3>Бронирования</h3>
                            <span class="panel-badge"><?= count($bookings) ?></span>
                        </div>

                        <?php if ($bookings): ?>
                            <div class="cabinet-table-wrap">
                                <table class="cabinet-table">
                                    <thead>
                                        <tr>
                                            <th>Автомобиль</th>
                                            <th>Стоимость</th>
                                            <th>Статус</th>
                                            <th>До</th>
                                            <th>Создана</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $b): ?>
                                            <?php [$sText, $sClass] = $bookingStatuses[$b['status']] ?? [$b['status'], 'pill-muted']; ?>
                                            <tr>
                                                <td>
                                                    <span class="cell-title">Volvo <?= e($b['model_name']) ?></span>
                                                    <span class="cell-sub"><?= e($b['year']) ?> г.</span>
                                                </td>
                                                <td><?= e($fmtMoney($b['price'])) ?></td>
                                                <td><span class="pill <?= e($sClass) ?>"><?= e($sText) ?></span></td>
                                                <td><?= e($fmtDate($b['expires_at'] ?? null)) ?></td>
                                                <td><?= e($fmtDate($b['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="cabinet-empty">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>
                                </svg>
                                <p>Нет активных броней. <a href="index.php#models">Забронировать →</a></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Purchases -->
                    <div class="cabinet-panel">
                        <div class="cabinet-panel-head">
                            <h3>История покупок</h3>
                            <span class="panel-badge"><?= count($sales) ?></span>
                        </div>

                        <?php if ($sales): ?>
                            <div class="cabinet-table-wrap">
                                <table class="cabinet-table">
                                    <thead>
                                        <tr>
                                            <th>Автомобиль</th>
                                            <th>Договор</th>
                                            <th>Стоимость</th>
                                            <th>Оплата</th>
                                            <th>Дата</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sales as $s): ?>
                                            <tr>
                                                <td>
                                                    <span class="cell-title">Volvo <?= e($s['model_name']) ?></span>
                                                    <span class="cell-sub"><?= e($s['year']) ?> г.</span>
                                                </td>
                                                <td><?= e($s['contract_number']) ?></td>
                                                <td><?= e($fmtMoney($s['price'])) ?></td>
                                                <td><?= e($paymentLabels[$s['payment_type']] ?? $s['payment_type']) ?></td>
                                                <td><?= e($fmtDate($s['sold_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="cabinet-empty">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <p>Покупок пока нет. <a href="index.php#models">Смотреть автомобили →</a></p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <?php require 'includes/footer.php'; ?>

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