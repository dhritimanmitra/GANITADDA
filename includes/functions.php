<?php
declare(strict_types=1);

/* ---------- Output & URLs ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path))); exit; }
function money($n): string { return '₹' . number_format((float)$n, ((float)$n == floor((float)$n)) ? 0 : 2); }

/* ---------- Flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes_html(): string
{
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as [$t, $m]) {
        $cls = $t === 'error' ? 'danger' : e($t);
        $out .= '<div class="alert alert-' . $cls . ' alert-dismissible fade show" role="alert">' . e($m)
              . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Your session expired. Please go back, refresh the page and try again.');
    }
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

/* ---------- Input helpers ---------- */
function post(string $k, string $default = ''): string { return trim((string)($_POST[$k] ?? $default)); }
function get(string $k, string $default = ''): string { return trim((string)($_GET[$k] ?? $default)); }
function valid_email(string $s): bool { return (bool)filter_var($s, FILTER_VALIDATE_EMAIL) && strlen($s) <= 190; }
function valid_mobile(string $s): bool { return (bool)preg_match('/^[0-9+\- ]{10,15}$/', $s); }
function valid_class(string $s): bool { return in_array($s, ['IX', 'X', 'Other'], true); }
function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? $s : 'item';
}
function unique_slug(string $table, string $title, ?int $ignoreId = null): string
{
    if (!in_array($table, ['courses', 'worksheets', 'categories'], true)) { throw new InvalidArgumentException('bad table'); }
    $base = slugify($title); $slug = $base; $i = 2;
    while (fetch_value("SELECT id FROM `$table` WHERE slug = ? AND id <> ?", [$slug, $ignoreId ?? 0])) { $slug = $base . '-' . $i++; }
    return $slug;
}

/* ---------- Website settings (cached per request) ---------- */
function settings(): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach (fetch_all('SELECT setting_key, setting_value FROM website_settings') as $r) { $all[$r['setting_key']] = (string)$r['setting_value']; }
    }
    return $all;
}
function setting(string $key, string $default = ''): string { $s = settings(); return ($s[$key] ?? '') !== '' ? $s[$key] : $default; }
function save_setting(string $key, string $value): void
{
    q('INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$key, $value]);
}
function whatsapp_link(string $text = ''): string
{
    $n = preg_replace('/\D+/', '', setting('contact_whatsapp'));
    if ($n === '') { return ''; }
    if (strlen($n) === 10) { $n = '91' . $n; }
    return 'https://wa.me/' . $n . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/* ---------- Secure file uploads ---------- */
function private_dir(string $kind): string { return rtrim(STORAGE_PATH, '/\\') . '/' . ($kind === 'solution' ? 'solutions' : 'worksheets'); }
function public_upload_dir(): string { return ROOT_PATH . '/assets/uploads'; }

/**
 * Validate and store one uploaded file.
 * $kind: worksheet | solution (PDF, private) or image (JPG/PNG/WebP, public).
 * Returns ['ok'=>true,'stored'=>name] or ['ok'=>false,'error'=>text].
 */
function handle_upload(array $file, string $kind): array
{
    if (!in_array($kind, ['worksheet', 'solution', 'image'], true)) { return ['ok' => false, 'error' => 'Invalid upload type.']; }
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) { return ['ok' => false, 'error' => 'No file selected.']; }
    if ($err !== UPLOAD_ERR_OK) { return ['ok' => false, 'error' => 'Upload failed (code ' . (int)$err . '). The file may be too large for the server.']; }
    if (!is_uploaded_file($file['tmp_name'])) { return ['ok' => false, 'error' => 'Invalid upload.']; }

    $max = (int)(MAX_UPLOAD_MB * 1048576);
    if ($file['size'] <= 0 || $file['size'] > $max) { return ['ok' => false, 'error' => 'File must be under ' . MAX_UPLOAD_MB . ' MB.']; }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);

    if ($kind === 'image') {
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        if (!isset($allowed[$ext]) || $allowed[$ext] !== $mime || @getimagesize($file['tmp_name']) === false) {
            return ['ok' => false, 'error' => 'Only JPG, PNG or WebP images are allowed.'];
        }
        $dir = public_upload_dir();
    } else {
        $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($ext !== 'pdf' || $mime !== 'application/pdf' || $head !== '%PDF-') { return ['ok' => false, 'error' => 'Only PDF files are allowed here.']; }
        $dir = private_dir($kind);
    }
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { return ['ok' => false, 'error' => 'Upload folder is not writable.']; }

    $ext = $ext === 'jpeg' ? 'jpg' : $ext;
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) { return ['ok' => false, 'error' => 'Could not save the file.']; }
    @chmod($dir . '/' . $stored, 0644);

    q('INSERT INTO uploads (original_name, stored_name, location, kind, mime_type, size_bytes, uploaded_by) VALUES (?,?,?,?,?,?,?)',
      [mb_substr(basename((string)$file['name']), 0, 255), $stored, $kind === 'image' ? 'public' : 'private', $kind, $mime, (int)$file['size'], $_SESSION['admin_id'] ?? null]);
    return ['ok' => true, 'stored' => $stored];
}

function delete_stored_file(?string $stored, string $kind): void
{
    if (!$stored || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/', $stored)) { return; }
    $path = ($kind === 'image' ? public_upload_dir() : private_dir($kind)) . '/' . $stored;
    if (is_file($path)) { @unlink($path); }
}

function public_image_url(?string $stored, string $fallback = 'assets/images/placeholder.svg'): string
{
    return ($stored && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $stored)) ? url('assets/uploads/' . $stored) : url($fallback);
}

function new_order_no(): string { return 'GA' . date('ymd') . strtoupper(bin2hex(random_bytes(3))); }
