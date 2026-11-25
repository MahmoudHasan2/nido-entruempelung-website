<?php
declare(strict_types=1);

/**
 * Admin authentication: Argon2id/bcrypt hashes, session fixation protection,
 * login rate limiting (per IP and per username), idle timeout, forced password change.
 */

const LOGIN_MAX_ATTEMPTS = 5;      // failed attempts per window …
const LOGIN_WINDOW = 900;          // … of 15 minutes
const SESSION_IDLE = 7200;         // 2 hours

function login_blocked(string $ip, string $username): bool
{
    $since = time() - LOGIN_WINDOW;
    q('DELETE FROM login_attempts WHERE created_at < ?', [$since - 86400]);
    $byIp = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE ip=? AND created_at>=?', [$ip, $since]);
    $byUser = (int) q_val('SELECT COUNT(*) FROM login_attempts WHERE username=? AND created_at>=?', [mb_strtolower($username), $since]);
    return $byIp >= LOGIN_MAX_ATTEMPTS || $byUser >= LOGIN_MAX_ATTEMPTS * 2;
}

function login_attempt_failed(string $ip, string $username): void
{
    q('INSERT INTO login_attempts (ip,username,created_at) VALUES (?,?,?)', [$ip, mb_strtolower($username), time()]);
}

/** @return array{ok:bool,error?:string} */
function admin_login(string $username, string $password): array
{
    session_boot();
    $ip = client_ip();
    if (login_blocked($ip, $username)) {
        return ['ok' => false, 'error' => 'Zu viele Anmeldeversuche. Bitte warten Sie 15 Minuten und versuchen Sie es dann erneut.'];
    }
    $u = q_one('SELECT * FROM users WHERE username = ?', [$username]);
    // always run a hash verification so timing does not reveal valid usernames
    $hash = $u['password_hash'] ?? password_hash('x', password_algo());
    $valid = password_verify($password, $hash) && $u !== null;
    if (!$valid) {
        login_attempt_failed($ip, $username);
        error_log('[auth] failed login for "' . substr($username, 0, 40) . '" from ' . $ip);
        return ['ok' => false, 'error' => 'Benutzername oder Passwort ist falsch.'];
    }
    if (password_needs_rehash($u['password_hash'], password_algo())) {
        q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($password, password_algo()), $u['id']]);
    }
    q('DELETE FROM login_attempts WHERE ip=?', [$ip]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    $_SESSION['last'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    q('UPDATE users SET last_login=? WHERE id=?', [now(), $u['id']]);
    return ['ok' => true];
}

function admin_logout(): void
{
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

function admin_user(): ?array
{
    static $u = false;
    if ($u !== false) {
        return $u;
    }
    session_boot();
    if (empty($_SESSION['uid'])) {
        return $u = null;
    }
    if (time() - (int) ($_SESSION['last'] ?? 0) > SESSION_IDLE) {
        admin_logout();
        return $u = null;
    }
    $_SESSION['last'] = time();
    return $u = q_one('SELECT id, username, must_change, last_login FROM users WHERE id=?', [(int) $_SESSION['uid']]);
}

function password_problem(string $new, string $username): ?string
{
    if (mb_strlen($new) < 10) {
        return 'Das neue Passwort muss mindestens 10 Zeichen lang sein.';
    }
    if (mb_strlen($new) > 200) {
        return 'Das neue Passwort ist zu lang.';
    }
    $weak = ['ChangeMe-2026!', 'admin', 'password', 'passwort123', '1234567890', 'nido2026', 'nidoentruempelung', 'entruempelung'];
    $l = mb_strtolower($new);
    if (in_array($l, $weak, true) || str_contains($l, mb_strtolower($username))) {
        return 'Dieses Passwort ist zu leicht zu erraten. Bitte wählen Sie ein anderes.';
    }
    if (preg_match('/^(.)\1+$/u', $new)) {
        return 'Das Passwort darf nicht nur aus einem wiederholten Zeichen bestehen.';
    }
    return null;
}

/** @return array{ok:bool,error?:string} */
function admin_change_password(int $uid, string $current, string $new, string $confirm): array
{
    $u = q_one('SELECT * FROM users WHERE id=?', [$uid]);
    if (!$u || !password_verify($current, $u['password_hash'])) {
        return ['ok' => false, 'error' => 'Das aktuelle Passwort ist falsch.'];
    }
    if ($new !== $confirm) {
        return ['ok' => false, 'error' => 'Die beiden neuen Passwörter stimmen nicht überein.'];
    }
    if (hash_equals($current, $new)) {
        return ['ok' => false, 'error' => 'Das neue Passwort muss sich vom aktuellen unterscheiden.'];
    }
    if ($p = password_problem($new, (string) $u['username'])) {
        return ['ok' => false, 'error' => $p];
    }
    q('UPDATE users SET password_hash=?, must_change=0 WHERE id=?', [password_hash($new, password_algo()), $uid]);
    session_regenerate_id(true);
    return ['ok' => true];
}
