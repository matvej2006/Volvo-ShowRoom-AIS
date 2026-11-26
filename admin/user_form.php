<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

require_admin();

$pdo = db();

$roleOptions = [
    'client'  => 'Клиент',
    'manager' => 'Менеджер',
    'admin'   => 'Администратор',
];

$values = [
    'name'      => '',
    'email'     => '',
    'phone'     => '',
    'role'      => 'client',
    'is_active' => '1',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $values['name']      = trim((string) ($_POST['name'] ?? ''));
    $values['email']     = trim((string) ($_POST['email'] ?? ''));
    $values['phone']     = trim((string) ($_POST['phone'] ?? ''));
    $values['role']      = (string) ($_POST['role'] ?? '');
    $values['is_active'] = (string) ($_POST['is_active'] ?? '1');

    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');

    // Имя
    if ($values['name'] === '' || mb_strlen($values['name']) < 2) {
        $errors['name'] = 'Введите имя (минимум 2 символа).';
    } elseif (mb_strlen($values['name']) > 100) {
        $errors['name'] = 'Имя слишком длинное (максимум 100 символов).';
    }

    // Email
    $email = $values['email'];
    if ($email === '') {
        $errors['email'] = 'Введите email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email.';
    } elseif (mb_strlen($email) > 150) {
        $errors['email'] = 'Email слишком длинный.';
    } else {
        $email = mb_strtolower($email);
        $values['email'] = $email;

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            $errors['email'] = 'Пользователь с таким email уже существует.';
        }
    }

    // Телефон
    if ($values['phone'] !== '' && !preg_match('/^\+?[0-9\s\-()]{7,30}$/', $values['phone'])) {
        $errors['phone'] = 'Некорректный номер телефона.';
    }

    // Пароль
    if ($password === '') {
        $errors['password'] = 'Введите пароль.';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не короче 6 символов.';
    }
    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Пароли не совпадают.';
    }

    // Роль и статус
    if (!isset($roleOptions[$values['role']])) {
        $errors['role'] = 'Недопустимая роль.';
    }
    if (!in_array($values['is_active'], ['0', '1'], true)) {
        $errors['is_active'] = 'Недопустимый статус.';
    }

    if ($errors === []) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, email, phone, password_hash, role, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $values['name'],
                $values['email'],
                $values['phone'] !== '' ? $values['phone'] : null,
                $hash,
                $values['role'],
                (int) $values['is_active'],
            ]);

            $newId = (int) $pdo->lastInsertId();

            log_action('user.create', 'users', $newId);
            flash('success', 'Пользователь добавлен.');
            redirect(app_base() . 'admin/users.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors['email'] = 'Пользователь с таким email уже существует.';
            } else {
                $errors['general'] = 'Не удалось добавить пользователя. Попробуйте ещё раз.';
            }
        }
    }
}

/* ---------- Превью ---------- */
$initials = '';
$words = preg_split('/\s+/u', trim((string) $values['name']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
foreach (array_slice($words, 0, 2) as $word) {
    $initials .= mb_strtoupper(mb_substr($word, 0, 1));
}
if ($initials === '') {
    $initials = '?';
}

$rolePills = [
    'admin'   => ['Администратор', 'pill-new'],
    'manager' => ['Менеджер', 'pill-progress'],
    'client'  => ['Клиент', 'pill-confirmed'],
];
$rolePill = $rolePills[$values['role']] ?? ['—', 'pill-canceled'];
$statusPill = $values['is_active'] === '1'
    ? ['Активен', 'pill-confirmed']
    : ['Заблокирован', 'pill-canceled'];

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Добавление пользователя — VolvoCarsBy</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/custom-select.css">
</head>

<body class="admin-body">

    <?php $base = '../'; require __DIR__ . '/../includes/admin_header.php'; ?>

    <main class="admin-main">

        <div class="admin-page-head">
            <div>
                <h1 class="admin-title">Добавление <span>· новый пользователь</span></h1>
                <p class="admin-subtitle">Создание учётной записи клиента или сотрудника</p>
            </div>
            <a class="a-btn a-btn-ghost" href="users.php">← К списку пользователей</a>
        </div>

        <div class="a-form-grid">
            <form class="a-form-card" id="userCreateForm" method="post" action="user_form.php">
                <?= csrf_field() ?>

                <?php if (isset($errors['general'])): ?>
                    <div class="a-alert a-alert-error"><?= e($errors['general']) ?></div>
                <?php elseif ($errors !== []): ?>
                    <div class="a-alert a-alert-error">Проверьте выделенные поля.</div>
                <?php endif; ?>

                <section class="a-form-section">
                    <h2>Данные пользователя</h2>

                    <div class="a-form-row">
                        <div class="a-field">
                            <label for="name">Имя</label>
                            <input type="text" id="name" name="name" class="a-input" maxlength="100"
                                   value="<?= e((string) $values['name']) ?>" required
                                   placeholder="Имя и фамилия">
                            <?php if (isset($errors['name'])): ?>
                                <span class="a-field-error"><?= e($errors['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="a-field">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="a-input" maxlength="150"
                                   value="<?= e((string) $values['email']) ?>" required
                                   placeholder="user@example.com" autocomplete="off">
                            <?php if (isset($errors['email'])): ?>
                                <span class="a-field-error"><?= e($errors['email']) ?></span>
                            <?php else: ?>
                                <span class="a-field-hint">Используется как логин, должен быть уникальным.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="a-form-row">
                        <div class="a-field">
                            <label for="phone">Телефон</label>
                            <input type="tel" id="phone" name="phone" class="a-input" maxlength="30"
                                   value="<?= e((string) $values['phone']) ?>"
                                   placeholder="+375 (__) ___-__-__">
                            <?php if (isset($errors['phone'])): ?>
                                <span class="a-field-error"><?= e($errors['phone']) ?></span>
                            <?php else: ?>
                                <span class="a-field-hint">Необязательно.</span>
                            <?php endif; ?>
                        </div>
                        <div class="a-field">
                            <label for="role">Роль</label>
                            <select id="role" name="role" class="a-select">
                                <?php foreach ($roleOptions as $val => $label): ?>
                                    <option value="<?= e($val) ?>" <?= $values['role'] === $val ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['role'])): ?>
                                <span class="a-field-error"><?= e($errors['role']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="a-form-section">
                    <h2>Доступ</h2>

                    <div class="a-form-row">
                        <div class="a-field">
                            <label for="password">Пароль</label>
                            <input type="password" id="password" name="password" class="a-input"
                                   placeholder="Минимум 6 символов" autocomplete="new-password" required>
                            <?php if (isset($errors['password'])): ?>
                                <span class="a-field-error"><?= e($errors['password']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="a-field">
                            <label for="password_confirm">Подтверждение пароля</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="a-input"
                                   placeholder="Повторите пароль" autocomplete="new-password" required>
                            <?php if (isset($errors['password_confirm'])): ?>
                                <span class="a-field-error"><?= e($errors['password_confirm']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="a-field">
                        <label for="is_active">Статус</label>
                        <select id="is_active" name="is_active" class="a-select">
                            <option value="1" <?= $values['is_active'] === '1' ? 'selected' : '' ?>>Активен</option>
                            <option value="0" <?= $values['is_active'] === '0' ? 'selected' : '' ?>>Заблокирован</option>
                        </select>
                        <?php if (isset($errors['is_active'])): ?>
                            <span class="a-field-error"><?= e($errors['is_active']) ?></span>
                        <?php else: ?>
                            <span class="a-field-hint">Заблокированный пользователь не сможет войти.</span>
                        <?php endif; ?>
                    </div>
                </section>

                <div class="a-form-actions">
                    <a class="a-btn a-btn-ghost" href="users.php">Отмена</a>
                    <button type="submit" class="a-btn a-btn-primary">Добавить пользователя</button>
                </div>
            </form>

            <aside class="a-side">
                <div class="a-user-preview">
                    <div class="a-user-avatar" id="previewAvatar"><?= e($initials) ?></div>
                    <div class="a-user-preview-name" id="previewName">
                        <?= $values['name'] !== '' ? e($values['name']) : 'Новый пользователь' ?>
                    </div>
                    <div class="a-user-preview-pills">
                        <span class="pill <?= e($rolePill[1]) ?>" id="previewRole"><?= e($rolePill[0]) ?></span>
                        <span class="pill <?= e($statusPill[1]) ?>" id="previewStatus"><?= e($statusPill[0]) ?></span>
                    </div>
                </div>

                <div class="a-note">
                    <h3>Что учитывать</h3>
                    <p>Email уникален и используется для входа.</p>
                    <p>Пароль хранится в виде bcrypt-хэша; минимум 6 символов.</p>
                    <p>Роль определяет доступ: клиент — кабинет, менеджер — заявки/продажи, администратор — полный доступ.</p>
                </div>
            </aside>
        </div>

        <script>
            (function () {
                var form = document.getElementById('userCreateForm');
                if (!form) {
                    return;
                }

                var nameInput = document.getElementById('name');
                var roleSel   = document.getElementById('role');
                var statusSel = document.getElementById('is_active');

                var pAvatar = document.getElementById('previewAvatar');
                var pName   = document.getElementById('previewName');
                var pRole   = document.getElementById('previewRole');
                var pStatus = document.getElementById('previewStatus');

                var roleMap = {
                    client:  ['Клиент', 'pill-confirmed'],
                    manager: ['Менеджер', 'pill-progress'],
                    admin:   ['Администратор', 'pill-new']
                };
                var statusMap = {
                    '1': ['Активен', 'pill-confirmed'],
                    '0': ['Заблокирован', 'pill-canceled']
                };

                function initials(name) {
                    var parts = name.split(/\s+/).filter(Boolean).slice(0, 2);
                    var out = '';
                    for (var i = 0; i < parts.length; i++) {
                        out += parts[i].charAt(0).toUpperCase();
                    }
                    return out;
                }

                function updatePreview() {
                    var name = nameInput ? nameInput.value.trim() : '';

                    pName.textContent = name || 'Новый пользователь';
                    pAvatar.textContent = initials(name) || '?';

                    var r = roleMap[roleSel ? roleSel.value : ''] || ['—', 'pill-canceled'];
                    pRole.textContent = r[0];
                    pRole.className = 'pill ' + r[1];

                    var s = statusMap[statusSel ? statusSel.value : ''] || ['—', 'pill-canceled'];
                    pStatus.textContent = s[0];
                    pStatus.className = 'pill ' + s[1];
                }

                [nameInput, roleSel, statusSel].forEach(function (el) {
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
