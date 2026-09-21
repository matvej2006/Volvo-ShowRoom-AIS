<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function auth_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];
    session_regenerate_id(true);
    return true;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    return (current_user()['role'] ?? null) === 'admin';
}

function is_manager(): bool
{
    return (current_user()['role'] ?? null) === 'manager';
}

function is_client(): bool
{
    return (current_user()['role'] ?? null) === 'client';
}

function require_login(): void
{
    if (!is_logged()) {
        flash('error', 'Требуется вход');
        redirect('/login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        redirect('/403.php');
    }
}

function require_admin(): void
{
    require_role('admin');
}

function require_manager(): void
{
    require_role('manager', 'admin');
}

function require_client(): void
{
    require_role('client');
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $p['path'],
            $p['domain'],
            $p['secure'],
            $p['httponly']
        );
    }
    session_destroy();
}
