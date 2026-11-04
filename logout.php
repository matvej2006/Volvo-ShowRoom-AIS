<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';

// Журнал пишем до уничтожения сессии, иначе user_id уже не определить
if (is_logged()) {
    log_action('user.logout');
}

auth_logout();

redirect('index.php');
