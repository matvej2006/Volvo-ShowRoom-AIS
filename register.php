<?php

declare(strict_types=1);

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

            <div class="auth-error"></div>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Имя</label>
                        <input type="text" id="name" name="name" value="" placeholder="Ваше имя" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Телефон</label>
                        <input type="tel" id="phone" name="phone" value="" placeholder="+375 (__) ___-__-__">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="" placeholder="your@email.com" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Пароль</label>
                        <input type="password" id="password" name="password" placeholder="Минимум 6 символов" required>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm">Подтверждение</label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Повторите пароль" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Зарегистрироваться</button>
            </form>

            <div class="auth-footer">
                Уже есть аккаунт? <a href="/login.php">Войти</a>
            </div>
        </div>
    </div>
</body>

</html>