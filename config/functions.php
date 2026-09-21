<?php

declare(strict_types=1);

// Экранирование вывода (XSS)
function e($value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

// Редирект и остановка скрипта.
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// Flash-сообщения: flash('success','Текст') — записать, flash('success') — прочитать и удалить.
function flash(string $key, ?string $message = null): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

// CSRF-токен.
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

// Скрытое поле для формы.
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

// Проверка CSRF. Бросает исключение при несовпадении.
function csrf_check(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $token = $_POST['_csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF-токен недействителен');
    }
}

// Старое значение поля после неудачной валидации.
function old(string $field, $default = '')
{
    return $_SESSION['_old'][$field] ?? $default;
}

// Сохранить введённые данные перед редиректом.
function keep_old(array $data): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    unset($data['_csrf'], $data['password'], $data['password_confirm']);
    $_SESSION['_old'] = $data;
}

// Очистить old после успешного сохранения.
function clear_old(): void
{
    unset($_SESSION['_old']);
}

// Записать действие в журнал.
function log_action(string $action, ?string $entity = null, ?int $entityId = null): void
{
    $userId = $_SESSION['user']['id'] ?? null;

    $stmt = db()->prepare(
        'INSERT INTO activity_log (user_id, action, entity, entity_id)
         VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $entity, $entityId]);
}

//Простой редирект назад.
function back(): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect($ref);
}
