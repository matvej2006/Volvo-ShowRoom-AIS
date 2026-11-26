<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_admin();

$pdo = db();

$flashSuccess = flash('success');
$flashError   = flash('error');

/* ---------- Параметры фильтрации, поиска и сортировки ---------- */
$q      = trim((string) ($_GET['search'] ?? ''));
$role   = (string) ($_GET['role'] ?? '');
$status = (string) ($_GET['status'] ?? '');

if (mb_strlen($q) > 100) {
    $q = mb_substr($q, 0, 100);
}

$roleLabels = [
    'admin'   => 'Администратор',
    'manager' => 'Менеджер',
    'client'  => 'Клиент',
];
if (!isset($roleLabels[$role])) {
    $role = '';
}

if (!in_array($status, ['0', '1'], true)) {
    $status = '';
}

$sortKeys = [
    'id'      => 'id',
    'name'    => 'name',
    'email'   => 'email',
    'role'    => 'role',
    'status'  => 'is_active',
    'created' => 'created_at',
];

$sortKey = (string) ($_GET['sort'] ?? 'created');
if (!isset($sortKeys[$sortKey])) {
    $sortKey = 'created';
}
$sortColumn = $sortKeys[$sortKey];

$dirParam = strtolower((string) ($_GET['dir'] ?? ''));
if ($dirParam !== 'asc' && $dirParam !== 'desc') {
    $dirParam = $sortKey === 'created' ? 'desc' : 'asc';
}
$dir = $dirParam;

/* ---------- Блокировка / разблокировка пользователя ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    csrf_check();

    $targetId  = (int) ($_POST['user_id'] ?? 0);
    $currentId = (int) current_user()['id'];

    $redirectParams = array_filter([
        'search' => $q,
        'role'   => $role,
        'status' => $status,
        'sort'   => $sortKey,
        'dir'    => $dir,
    ], static fn ($value): bool => $value !== '' && $value !== null);

    $redirectUrl = app_base() . 'admin/users.php'
        . ($redirectParams !== [] ? '?' . http_build_query($redirectParams) : '');

    if ($targetId <= 0) {
        flash('error', 'Пользователь не найден.');
        redirect($redirectUrl);
    }
    if ($targetId === $currentId) {
        flash('error', 'Нельзя заблокировать собственную учётную запись.');
        redirect($redirectUrl);
    }

    $stmt = $pdo->prepare('SELECT id, is_active FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('error', 'Пользователь не найден.');
        redirect($redirectUrl);
    }

    $newActive = (int) $target['is_active'] === 1 ? 0 : 1;

    $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?');
    $stmt->execute([$newActive, $targetId]);

    log_action($newActive === 1 ? 'user.unblock' : 'user.block', 'users', $targetId);
    flash('success', $newActive === 1 ? 'Пользователь разблокирован.' : 'Пользователь заблокирован.');
    redirect($redirectUrl);
}

/* ---------- Сборка запроса ---------- */
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = 'name LIKE ?';
    $params[] = '%' . addcslashes($q, "\\%_") . '%';
}
if ($role !== '') {
    $where[]  = 'role = ?';
    $params[] = $role;
}
if ($status !== '') {
    $where[]  = 'is_active = ?';
    $params[] = (int) $status;
}

$sql = 'SELECT id, name, email, phone, role, is_active, created_at FROM users';
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $sortColumn . ' ' . strtoupper($dir);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$isFiltered = ($q !== '' || $role !== '' || $status !== '');

/* ---------- Хелперы ссылок и сортировки ---------- */
$buildQuery = static function (array $extra) use ($q, $role, $status): string {
    $params = array_merge([
        'search' => $q,
        'role'   => $role,
        'status' => $status,
    ], $extra);

    $clean = [];
    foreach ($params as $key => $value) {
        if ($value !== '' && $value !== null) {
            $clean[$key] = $value;
        }
    }

    return 'users.php' . ($clean !== [] ? '?' . http_build_query($clean) : '');
};

$renderSort = static function (string $column, string $label) use ($sortKey, $dir, $buildQuery): string {
    $isActive = $sortKey === $column;
    $nextDir  = $isActive
        ? ($dir === 'asc' ? 'desc' : 'asc')
        : ($column === 'created' ? 'desc' : 'asc');

    $url = $buildQuery([
        'sort' => $column,
        'dir'  => $nextDir,
    ]);

    $indicator = $isActive ? ($dir === 'asc' ? '↑' : '↓') : '↕';
    $class     = 'sort-link' . ($isActive ? ' is-active' : '');

    return '<a class="' . $class . '" href="' . e($url) . '">' . e($label)
        . ' <span class="sort-ind">' . $indicator . '</span></a>';
};

$fmtDate = static fn (?string $dt): string => $dt ? date('d.m.Y H:i', strtotime($dt)) : '—';

$rolePills = [
    'admin'   => 'pill-new',
    'manager' => 'pill-progress',
    'client'  => 'pill-confirmed',
];

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Пользователи — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/custom-select.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <div class="admin-page-head">
            <div>
                <h1 class="admin-title">Пользователи <span>· все аккаунты</span></h1>
                <p class="admin-subtitle">
                    <?= $isFiltered ? 'Найдено' : 'Всего' ?>: <?= count($users) ?>
                </p>
            </div>
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <a class="a-btn a-btn-primary" href="user_form.php">+ Добавить пользователя</a>
                <div class="admin-date"><?= date('d.m.Y, H:i') ?></div>
            </div>
        </div>

        <?php if ($flashSuccess !== null): ?>
            <div class="a-alert a-alert-success"><?= e($flashSuccess) ?></div>
        <?php endif; ?>

        <?php if ($flashError !== null): ?>
            <div class="a-alert a-alert-error"><?= e($flashError) ?></div>
        <?php endif; ?>

        <form class="panel users-toolbar-panel" method="get" action="users.php">
            <div class="panel-body">
                <input type="hidden" name="sort" value="<?= e($sortKey) ?>">
                <input type="hidden" name="dir" value="<?= e($dir) ?>">

                <div class="users-toolbar">
                    <div class="a-field">
                        <label for="search">Поиск по имени</label>
                        <input type="text" id="search" name="search" class="a-input" maxlength="100"
                               value="<?= e($q) ?>" placeholder="Имя пользователя">
                    </div>

                    <div class="a-field">
                        <label for="role">Роль</label>
                        <select id="role" name="role" class="a-select">
                            <option value="">Все роли</option>
                            <?php foreach ($roleLabels as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= $role === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="a-field">
                        <label for="status">Статус</label>
                        <select id="status" name="status" class="a-select">
                            <option value="">Все</option>
                            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Активные</option>
                            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Заблокированные</option>
                        </select>
                    </div>

                    <div class="users-toolbar-actions">
                        <button type="submit" class="a-btn a-btn-primary">Найти</button>
                        <a class="a-btn a-btn-ghost" href="users.php">Сбросить</a>
                    </div>
                </div>
            </div>
        </form>

        <?php if ($users !== []): ?>
            <div class="panel">
                <div style="overflow-x:auto">
                    <table class="a-table">
                        <thead>
                            <tr>
                                <th><?= $renderSort('id', 'ID') ?></th>
                                <th><?= $renderSort('name', 'Имя') ?></th>
                                <th><?= $renderSort('email', 'Email') ?></th>
                                <th>Телефон</th>
                                <th><?= $renderSort('role', 'Роль') ?></th>
                                <th><?= $renderSort('status', 'Статус') ?></th>
                                <th><?= $renderSort('created', 'Регистрация') ?></th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $userRole   = (string) $user['role'];
                                $isActive   = (int) $user['is_active'] === 1;
                                $roleText   = $roleLabels[$userRole] ?? $userRole;
                                $rolePill   = $rolePills[$userRole] ?? 'pill-canceled';
                                $isSelf     = (int) $user['id'] === (int) current_user()['id'];
                                ?>
                                <tr>
                                    <td class="a-mono">#<?= (int) $user['id'] ?></td>
                                    <td class="a-strong"><?= e($user['name']) ?></td>
                                    <td><?= e($user['email']) ?></td>
                                    <td class="a-muted"><?= !empty($user['phone']) ? e($user['phone']) : '—' ?></td>
                                    <td><span class="pill <?= $rolePill ?>"><?= e($roleText) ?></span></td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="pill pill-confirmed">Активен</span>
                                        <?php else: ?>
                                            <span class="pill pill-canceled">Заблокирован</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="a-muted a-mono"><?= e($fmtDate($user['created_at'])) ?></td>
                                    <td>
                                        <form method="post" action="users.php?<?= e($_SERVER['QUERY_STRING'] ?? '') ?>"
                                              class="user-toggle-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <?php if ($isActive): ?>
                                                <button type="submit" class="a-btn a-btn-danger a-btn-sm"
                                                        <?= $isSelf ? 'disabled title="Нельзя заблокировать себя"' : '' ?>>
                                                    Заблокировать
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="a-btn a-btn-ghost a-btn-sm">Разблокировать</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <section class="panel">
                <div class="empty">
                    Пользователи не найдены.
                    <?php if ($isFiltered): ?>
                        <a href="users.php">Сбросить фильтры</a>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <script src="../assets/js/custom-select.js"></script>

</body>

</html>
