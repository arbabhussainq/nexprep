<?php
// ============================================================
// NexPrep - Core Configuration
// ============================================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'nexprep');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('APP_NAME', 'NexPrep');
define('APP_URL',  getenv('APP_URL') ?: 'http://localhost/nexprep');
define('APP_VERSION', '1.0.0');
define('SESSION_TIMEOUT', 1800);
define('POINTS_CORRECT',   4);
define('POINTS_WRONG',    -1);
define('POINTS_TIME_BONUS', 10);

// ============================================================
// Database Connection (PDO)
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]));
        }
    }
    return $pdo;
}

// ============================================================
// Session helpers
// ============================================================
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',   // Lax not Strict - fixes redirect issues
        ]);
        session_start();
    }
    if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > SESSION_TIMEOUT) {
        session_unset(); session_destroy(); session_start();
    }
    $_SESSION['last_active'] = time();
}

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        if (isAjax()) jsonResponse(false, 'Session expired. Please login again.', ['redirect' => APP_URL.'/index.php']);
        header('Location: '.APP_URL.'/index.php');
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        if (isAjax()) jsonResponse(false, 'Access denied.');
        $dest = in_array($_SESSION['role'], ['admin','instructor']) ? '/admin/dashboard.php' : '/student/dashboard.php';
        header('Location: '.APP_URL.$dest);
        exit;
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? 0,
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['role']       ?? '',
    ];
}

function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
           (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded')) ||
           !empty($_POST) || !empty($_GET['action']);
}

// ============================================================
// Utility helpers
// ============================================================
function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function jsonResponse(bool $success, string $message = '', array $data = []): never {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success'=>$success,'message'=>$message], $data));
    exit;
}

function redirect(string $url): never {
    header("Location: $url"); exit;
}

// Subject helpers
function subjectBadge(string $subject): string {
    $map = [
        'Physics'     => 'badge-physics',
        'Chemistry'   => 'badge-chem',
        'Mathematics' => 'badge-math',
        'English'     => 'badge-eng',
    ];
    return $map[$subject] ?? 'badge-pill';
}

function subjectIcon(string $subject): string {
    $map = [
        'Physics'     => 'bi-lightning-charge-fill',
        'Chemistry'   => 'bi-flask-fill',
        'Mathematics' => 'bi-calculator-fill',
        'English'     => 'bi-book-half',
    ];
    return $map[$subject] ?? 'bi-journal';
}

function subjectColor(string $subject): string {
    $map = [
        'Physics'     => '#7c3aed',
        'Chemistry'   => '#059669',
        'Mathematics' => '#d97706',
        'English'     => '#0284c7',
    ];
    return $map[$subject] ?? '#4f46e5';
}

function timeSince(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff/60).'m ago';
    if ($diff < 86400)  return floor($diff/3600).'h ago';
    if ($diff < 604800) return floor($diff/86400).'d ago';
    return date('d M Y', strtotime($datetime));
}
