<?php
declare(strict_types=1);

/**
 * Nido Entrümpelung – application bootstrap.
 * Loads environment, error handling, helpers, database + migrations.
 */

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('STORAGE', ROOT . '/storage');
define('UPLOADS', ROOT . '/uploads');

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

/* ---------------------------------------------------------------- env */
function load_env(string $file): array
{
    $out = [];
    if (!is_file($file)) {
        return $out;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $v = trim($v);
        if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
            $v = substr($v, 1, -1);
        }
        $out[trim($k)] = $v;
    }
    return $out;
}

function env(string $key, ?string $default = null): ?string
{
    static $file = null;
    $file ??= load_env(ROOT . '/.env');
    $real = getenv($key);
    if ($real !== false && $real !== '') {
        return $real;
    }
    return $file[$key] ?? $default;
}

$GLOBALS['APP_DEBUG'] = env('APP_ENV', 'production') === 'development';

error_reporting(E_ALL);
ini_set('display_errors', $GLOBALS['APP_DEBUG'] ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(STORAGE)) {
    @mkdir(STORAGE, 0750, true);
}
ini_set('error_log', STORAGE . '/php-error.log');

/* Defence in depth: make sure the private/upload folders always carry their access rules. */
(function (): void {
    $rules = [
        STORAGE . '/.htaccess' => "Require all denied\n",
        UPLOADS . '/.htaccess' => "Options -Indexes -ExecCGI\nRemoveHandler .php .phtml .php3 .php5 .php7 .php8\nRemoveType .php .phtml\n"
            . "<FilesMatch \"\\.(php|phtml|phar|pl|py|cgi|sh)$\">\n  Require all denied\n</FilesMatch>\n",
    ];
    foreach ($rules as $file => $content) {
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }
        if (!is_file($file)) {
            @file_put_contents($file, $content);
        }
    }
})();

set_exception_handler(function (Throwable $e): void {
    error_log('[uncaught] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="de"><meta charset="utf-8"><title>Fehler</title>'
        . '<body style="font-family:sans-serif;padding:3rem"><h1>Es ist ein Fehler aufgetreten.</h1>'
        . '<p>Bitte versuchen Sie es später erneut oder rufen Sie uns an.</p>';
    if ($GLOBALS['APP_DEBUG']) {
        echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
    }
    exit;
});

/* ------------------------------------------------------------ helpers */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function site_url(string $path = ''): string
{
    $base = rtrim((string) env('SITE_URL', ''), '/');
    if ($base === '') {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // never trust arbitrary Host header for absolute URLs outside localhost
        if (!preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
            $host = 'nido-entruempelung.de';
        }
        $base = (is_https() ? 'https://' : 'http://') . $host;
    }
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $to, int $code = 302): never
{
    header('Location: ' . $to, true, $code);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function client_ip(): string
{
    // Behind Hostinger's proxy the first X-Forwarded-For hop is the client.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (env('TRUST_PROXY', '1') === '1' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            $ip = $first;
        }
    }
    return $ip;
}

/** CSP nonce for the current request. */
function csp_nonce(): string
{
    static $n = null;
    return $n ??= rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}

function send_security_headers(bool $admin = false): void
{
    $n = csp_nonce();
    $csp = "default-src 'self'; "
        . "script-src 'self' 'nonce-$n'; "
        . "style-src 'self' 'nonce-$n'; "
        . "img-src 'self' data:; "
        . "font-src 'self'; "
        . "frame-src https://www.google.com https://maps.google.com; "
        . "connect-src 'self'; "
        . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    if ($admin) {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
}

/* ------------------------------------------------------------ session */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $dir = STORAGE . '/sessions';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    session_save_path($dir);
    session_name('nido_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '7200');
    session_start();
}

function csrf_token(): string
{
    session_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    session_boot();
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    session_boot();
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/* ----------------------------------------------------------- database */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $path = env('DB_PATH', STORAGE . '/nido.sqlite');
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function q_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function q_one(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function q_val(string $sql, array $params = []): mixed
{
    $r = q($sql, $params)->fetchColumn();
    return $r === false ? null : $r;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $s): string
{
    $s = mb_strtolower($s);
    $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-') ?: 'eintrag';
}

/* ------------------------------------------------------- core modules */
require_once APP . '/lib/schema.php';
require_once APP . '/lib/content.php';
require_once APP . '/lib/icons.php';
require_once APP . '/lib/media.php';

migrate();
