<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/db.php';

if (is_logged()) {
    redirect('index.php');
}

// Ошибки прошлой отправки, чтобы показать их после редиректа
$errors = $_SESSION['_errors'] ?? [];
unset($_SESSION['_errors']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $errors = [];

    // 1. Проверка токена
    csrf_check();

    // 2. Получаем и очищаем данные
    $email    = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    // 3. Валидация и проверка учётных данных
    if ($email === '' || $password === '') {
        $errors['general'] = 'Введите email и пароль';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['general'] = 'Некорректный email';
    } elseif (!auth_login($email, $password)) {
        // Не уточняем, что именно неверно — email или пароль
        $errors['general'] = 'Неверный email или пароль';
    }

    // 4. Успешный вход
    if (empty($errors)) {
        log_action('user.login');
        clear_old();
        redirect('index.php');
    }

    // 5. Были ошибки — сохраняем ввод и возвращаемся на форму
    keep_old($_POST);
    $_SESSION['_errors'] = $errors;
    redirect('login.php');
}

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход — VolvoCarsBy</title>
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
            max-width: 440px;
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
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <div class="auth-glass">
            <div class="auth-logo">
                <a href="index.php"><img onclick="location.href='index.php'" src="assets/images/volvo_new_logo-freelogovectors.net_.png" alt="Volvo"></a>
            </div>
            <h1 class="auth-title"><strong>Добро пожаловать</strong></h1>
            <p class="auth-subtitle">Войдите в личный кабинет VolvoCarsBy</p>


            <?php if (!empty($errors)): ?>
                <div class="auth-error">
                    <?= e($errors['general'] ?? 'Не удалось выполнить вход') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="150" value="<?= e(old('email')) ?>" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" placeholder="Введите пароль" required>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Войти</button>
            </form>

            <div class="auth-footer">
                Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
            </div>
        </div>
    </div>
</body>

</html>