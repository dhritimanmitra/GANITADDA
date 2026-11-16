<?php
declare(strict_types=1);

const LOGIN_MAX_FAILS = 5;
const LOGIN_WINDOW_MIN = 15;

function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45); }

function login_blocked(string $identifier): bool
{
    q('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    $n = (int)fetch_value('SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND ip = ? AND attempted_at > (NOW() - INTERVAL ' . LOGIN_WINDOW_MIN . ' MINUTE)',
        [strtolower($identifier), client_ip()]);
    return $n >= LOGIN_MAX_FAILS;
}
function login_fail(string $identifier): void { q('INSERT INTO login_attempts (identifier, ip) VALUES (?, ?)', [strtolower($identifier), client_ip()]); }
function login_clear(string $identifier): void { q('DELETE FROM login_attempts WHERE identifier = ? AND ip = ?', [strtolower($identifier), client_ip()]); }

/** @return string|null error message, or null on success */
function attempt_login(string $role, string $email, string $password): ?string
{
    $table = $role === 'admin' ? 'admins' : 'students';
    $email = strtolower(trim($email));
    if (login_blocked($email)) { return 'Too many failed attempts. Please wait ' . LOGIN_WINDOW_MIN . ' minutes and try again.'; }
    $u = fetch_one("SELECT * FROM `$table` WHERE email = ?", [$email]);
    // Always run a hash check so response time does not reveal whether the email exists.
    $hash = $u['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
    $ok = password_verify($password, $hash);
    if (!$u || !$ok) { login_fail($email); return 'Incorrect email or password.'; }
    if (!(int)$u['is_active']) { return 'This account is disabled. Please contact Ganit Adda.'; }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q("UPDATE `$table` SET password_hash = ? WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    login_clear($email);
    session_regenerate_id(true);
    $_SESSION[$role . '_id'] = (int)$u['id'];
    q("UPDATE `$table` SET last_login_at = NOW() WHERE id = ?", [$u['id']]);
    return null;
}

function current_admin(): ?array
{
    static $a = false;
    if ($a === false) { $a = isset($_SESSION['admin_id']) ? fetch_one('SELECT id, name, email FROM admins WHERE id = ? AND is_active = 1', [$_SESSION['admin_id']]) : null; }
    return $a;
}
function current_student(): ?array
{
    static $s = false;
    if ($s === false) { $s = isset($_SESSION['student_id']) ? fetch_one('SELECT id, full_name, email, mobile, class_level FROM students WHERE id = ? AND is_active = 1', [$_SESSION['student_id']]) : null; }
    return $s;
}
function require_admin(): array
{
    $a = current_admin();
    if (!$a) { unset($_SESSION['admin_id']); redirect('admin/login.php'); }
    return $a;
}
function require_student(): array
{
    $s = current_student();
    if (!$s) { unset($_SESSION['student_id']); flash('warning', 'Please log in to continue.'); redirect('login.php'); }
    return $s;
}
function logout_all(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
}

/** Can this student download the given worksheet file? Free ones: any logged-in student. Paid: only after a verified payment. */
function student_can_download(int $studentId, array $ws): bool
{
    if (!(int)$ws['allow_download'] || $ws['status'] !== 'published') { return false; }
    if ((int)$ws['is_free']) { return true; }
    return (bool)fetch_value(
        "SELECT 1 FROM order_items oi JOIN orders o ON o.id = oi.order_id JOIN payments p ON p.order_id = o.id
         WHERE oi.worksheet_id = ? AND o.student_id = ? AND o.status = 'paid' AND p.status = 'verified' LIMIT 1",
        [$ws['id'], $studentId]);
}
