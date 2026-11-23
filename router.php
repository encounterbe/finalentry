<?php

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if (preg_match('#^/(config|includes|lib|database|scripts|docs)(/|$)|/\.|\.(md|sql)$|^/router\.php$#', $path)) {
    http_response_code(403);
    exit('Forbidden');
}

return false;
