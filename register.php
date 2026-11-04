<?php

declare(strict_types=1);

require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/functions.php";
require_once __DIR__ . "/config/db.php";

if (is_logged()) {
    redirect("profile.php");
}

// Ошибки прошлой отправки (см. пункт 6), чтобы показать их после редиректа
$errors = $_SESSION['_errors'] ?? [];
unset($_SESSION['_errors']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    // 1. Проверка токена
    csrf_check();

    // 2. Получаем и очищаем данные
    $name     = trim((string)($_POST['name'] ?? ''));
    $email    = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $phone    = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');

    // 3. Валидация
    if ($name === '' || mb_strlen($name) < 2) {
        $errors['name'] = 'Введите имя (минимум 2 символа)';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Имя слишком длинное (максимум 100 символов)';
    }

    if ($email === '') {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    } elseif (mb_strlen($email) > 150) {
        $errors['email'] = 'Email слишком длинный';
    }

    if ($phone !== '' && !preg_match('/^\+?[0-9\s\-()]{7,30}$/', $phone)) {
        $errors['phone'] = 'Некорректный номер телефона';
    }

    if ($password === '') {
        $errors['password'] = 'Введите пароль';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не короче 6 символов';
    }

    if ($password !== $confirm) {
        $errors['password_confirm'] = 'Пароли не совпадают';
    }

    // 4. Проверка, что email ещё не занят
    if (!isset($errors['email'])) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors['email'] = 'Пользователь с таким email уже зарегистрирован';
        }
    }

    // 5. Если ошибок нет — создаём пользователя
    if (empty($errors)) {
        // Хэшируем пароль (bcrypt)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, phone, password_hash, role)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $phone ?: null, $hash, 'client']);

            $userId = (int)db()->lastInsertId();

            // Автоматически логиним нового клиента
            auth_login($email, $password);

            // Журнал пишем после входа, иначе user_id в логе останется пустым
            log_action('user.register', 'users', $userId);

            flash('success', 'Регистрация успешна. Добро пожаловать!');
            clear_old();
            redirect('profile.php');
        } catch (PDOException $e) {
            // На случай, если два человека одновременно отправили форму
            // и UNIQUE не сработал на этапе проверки
            if ($e->getCode() === '23000') {
                $errors['email'] = 'Этот email уже занят';
            } else {
                $errors['general'] = 'Не удалось сохранить пользователя. Попробуйте позже.';
            }
        }
    }

    // 6. Если были ошибки — сохраняем ввод и возвращаемся на форму
    if (!empty($errors)) {
        keep_old($_POST);
        // Сохраняем ошибки в сессию, чтобы показать их после редиректа
        $_SESSION['_errors'] = $errors;
        redirect('register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — VolvoCarsBy</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0d0d0d 100%);
            position: relative;
            overflow: hidden;
        }

        .auth-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 50% 50%, rgba(74, 109, 140, 0.06) 0%, transparent 70%);
            pointer-events: none;
        }

        .auth-glass {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 480px;
            margin: 0 24px;
            padding: 48px 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(40px) saturate(150%);
            -webkit-backdrop-filter: blur(40px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .auth-glass::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            border-radius: 32px 32px 0 0;
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-logo img {
            width: 120px;
            margin: 0 auto;
        }

        .auth-title {
            font-size: 28px;
            font-weight: 300;
            color: #ffffff;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .auth-title strong {
            font-weight: 500;
        }

        .auth-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.45);
            text-align: center;
            margin-bottom: 32px;
        }

        .auth-error {
            padding: 12px 16px;
            background: rgba(255, 59, 48, 0.1);
            border: 1px solid rgba(255, 59, 48, 0.2);
            border-radius: 12px;
            color: #ff6b6b;
            font-size: 13px;
            margin-bottom: 24px;
            text-align: center;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: #ffffff;
            font-size: 15px;
            font-family: var(--font-primary);
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }

        .form-group input:focus {
            border-color: rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.09);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.04);
        }

        .form-group input.is-invalid {
            border-color: rgba(255, 59, 48, 0.45);
            background: rgba(255, 59, 48, 0.06);
        }

        .field-error {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            line-height: 1.4;
            color: #ff8a8a;
        }

        .auth-submit {
            width: 100%;
            margin-top: 8px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.4);
        }

        .auth-footer a {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 600;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .auth-footer a:hover {
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .auth-glass {
                padding: 36px 24px;
                border-radius: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <div class="auth-glass">
            <div class="auth-logo">
                <a href="index.php"><img src="assets/images/volvo_new_logo-freelogovectors.net_.png" alt="Volvo"></a>
            </div>
            <h1 class="auth-title">Создать <strong>аккаунт</strong></h1>
            <p class="auth-subtitle">Зарегистрируйтесь в VolvoCarsBy</p>

            <?php if (!empty($errors)): ?>
                <div class="auth-error">
                    <?= e($errors['general'] ?? 'Проверьте правильность заполнения формы') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Имя</label>
                        <input type="text" id="name" name="name" maxlength="100" value="<?= e(old('name')) ?>" placeholder="Ваше имя" <?= isset($errors['name']) ? 'class="is-invalid"' : '' ?> required>
                        <?php if (isset($errors['name'])): ?>
                            <span class="field-error"><?= e($errors['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" maxlength="30" value="<?= e(old('phone')) ?>" placeholder="+375 (__) ___-__-__" <?= isset($errors['phone']) ? 'class="is-invalid"' : '' ?>>
                        <?php if (isset($errors['phone'])): ?>
                            <span class="field-error"><?= e($errors['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="150" value="<?= e(old('email')) ?>" placeholder="your@email.com" <?= isset($errors['email']) ? 'class="is-invalid"' : '' ?> required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="field-error"><?= e($errors['email']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" placeholder="Минимум 6 символов" <?= isset($errors['password']) ? 'class="is-invalid"' : '' ?> required>
                        <?php if (isset($errors['password'])): ?>
                            <span class="field-error"><?= e($errors['password']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Подтверждение</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Повторите пароль" <?= isset($errors['password_confirm']) ? 'class="is-invalid"' : '' ?> required>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <span class="field-error"><?= e($errors['password_confirm']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Зарегистрироваться</button>
            </form>

            <div class="auth-footer">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </div>
        </div>
    </div>
</body>

</html>