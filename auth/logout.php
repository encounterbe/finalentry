<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_post_with_csrf();

$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $params['path'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'],
]);
session_destroy();

redirect('login/');
