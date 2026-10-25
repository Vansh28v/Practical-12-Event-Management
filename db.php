<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'event_management';

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Start MySQL and import database.sql in phpMyAdmin. Check db.php credentials.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Invalid request token. Return to the form and try again.');
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function audit_event(mysqli $conn, ?int $eventId, string $action, string $details): void {
    $actor = 'local-admin';
    $stmt = $conn->prepare('INSERT INTO event_audit_log (event_id, actor, action, details) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $eventId, $actor, $action, $details);
    $stmt->execute();
}

function ensure_upload_dir(): string {
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'posters';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create the poster upload directory.');
    }
    return $dir;
}

function validate_poster(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['error' => 'Invalid upload request.'];
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return ['path' => null, 'name' => null];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['error' => 'Upload failed. Check the PHP upload size limits and try again.'];
    if (($file['size'] ?? 0) < 1 || $file['size'] > 2 * 1024 * 1024) {
        return ['error' => 'Poster must be smaller than or equal to 2 MB.'];
    }

    $tmp = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) return ['error' => 'The uploaded file is not valid.'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    if (!isset($allowed[$mime])) {
        return ['error' => 'Unsupported poster type. Upload JPG, PNG or WEBP only.'];
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false || !isset($imageInfo['mime']) || $imageInfo['mime'] !== $mime) {
        return ['error' => 'The uploaded file is not a valid image.'];
    }

    return ['path' => $tmp, 'extension' => $allowed[$mime], 'size' => (int)$file['size']];
}

function store_poster(array $upload): ?string {
    if (empty($upload['path'])) return null;
    $dir = ensure_upload_dir();
    $filename = bin2hex(random_bytes(16)) . '.' . $upload['extension'];
    $destination = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($upload['path'], $destination)) {
        throw new RuntimeException('Unable to save the uploaded poster.');
    }
    return 'uploads/posters/' . $filename;
}

function remove_poster(?string $relativePath): void {
    if (!$relativePath) return;
    $base = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'posters');
    $target = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
    if ($base && $target && str_starts_with($target, $base . DIRECTORY_SEPARATOR) && is_file($target)) {
        @unlink($target);
    }
}
