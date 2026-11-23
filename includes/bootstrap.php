<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Missing config/config.php. Copy config/config.example.php and fill in your values.');
}

$GLOBALS['config'] = require $configFile;

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set(config('app.timezone', 'UTC'));

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $appRoot = realpath(APP_ROOT) ?: APP_ROOT;
        $base = $docRoot !== '' && str_starts_with($appRoot, $docRoot)
            ? str_replace('\\', '/', substr($appRoot, strlen($docRoot)))
            : '';
        $base = rtrim($base, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' https://cdn.tailwindcss.com https://unpkg.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "img-src 'self' data: blob:; media-src 'self' blob:; worker-src 'self' blob:; "
        . "connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('finalentry_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => base_path() . '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    $idleLimit = 60 * 60 * 4;
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $idleLimit) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = time();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('db.host'),
            (int) config('db.port', 3306),
            config('db.name')
        );
        try {
            $pdo = new PDO($dsn, config('db.user'), config('db.pass'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
        } catch (PDOException $ex) {
            error_log('Database connection failed: ' . $ex->getMessage());
            http_response_code(500);
            exit('Database connection failed. Please try again later.');
        }
    }
    return $pdo;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

function require_post_with_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Method not allowed.');
    }
    if (!csrf_valid()) {
        http_response_code(403);
        exit('Invalid or expired form. Please reload the page and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function rate_limit_bucket(string $action, string $key): string
{
    return hash('sha256', $action . '|' . strtolower($key));
}

function too_many_attempts(string $action, string $key, int $maxAttempts, int $windowSeconds): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND attempted_at > (NOW() - INTERVAL ? SECOND)'
    );
    $stmt->execute([rate_limit_bucket($action, $key), $windowSeconds]);
    return (int) $stmt->fetchColumn() >= $maxAttempts;
}

function record_attempt(string $action, string $key): void
{
    db()->prepare('INSERT INTO rate_limits (bucket, attempted_at) VALUES (?, NOW())')
        ->execute([rate_limit_bucket($action, $key)]);

    if (random_int(1, 50) === 1) {
        db()->exec('DELETE FROM rate_limits WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }
}

function clear_attempts(string $action, string $key): void
{
    db()->prepare('DELETE FROM rate_limits WHERE bucket = ?')->execute([rate_limit_bucket($action, $key)]);
}

if (PHP_SAPI !== 'cli') {
    send_security_headers();
    start_session();
}
