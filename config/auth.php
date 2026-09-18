<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------- СОТРУДНИКИ ---------- */

function auth_login_user(string $email, string $password): bool
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

/* ---------- КЛИЕНТЫ ---------- */

function auth_login_client(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT * FROM clients WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $client = $stmt->fetch();

    if (!$client || !password_verify($password, $client['password_hash'])) {
        return false;
    }

    $_SESSION['client'] = [
        'id'    => (int)$client['id'],
        'name'  => $client['name'],
        'email' => $client['email'],
    ];
    session_regenerate_id(true);
    return true;
}

function current_client(): ?array
{
    return $_SESSION['client'] ?? null;
}

function is_client_logged(): bool
{
    return isset($_SESSION['client']);
}

function require_client(): void
{
    if (!is_client_logged()) {
        flash('error', 'Войдите в личный кабинет');
        redirect('/login.php');
    }
}

/* ---------- ОБЩЕЕ ---------- */

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
