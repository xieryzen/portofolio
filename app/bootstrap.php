<?php

declare(strict_types=1);

use App\Auth;
use App\Config;
use App\Database;
use App\Settings;
use App\View;

/**
 * Bootstrap aplikasi: autoloader, konfigurasi, session, database.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/helpers.php';
require BASE_PATH . '/app/Config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

Config::load(BASE_PATH);

require BASE_PATH . '/app/Settings.php';

date_default_timezone_set('Asia/Jakarta');
mb_internal_encoding('UTF-8');
setlocale(LC_ALL, 'id_ID.UTF-8', 'id_ID', 'C.UTF-8');

$debug = Config::bool('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ---- Session ----------------------------------------------------------
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $sessionPath = BASE_PATH . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0775, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    $lifetime = 60 * 60 * 6;
    $secure = is_secure();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) $lifetime);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => app_base_url() === '' ? '/' : app_base_url() . '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_name('portfolio_session');
    session_start();
}

// ---- Error & exception handling --------------------------------------
$logDirectory = BASE_PATH . '/storage/logs';
if (!is_dir($logDirectory)) {
    @mkdir($logDirectory, 0775, true);
}

$logFile = $logDirectory . '/app.log';

$logger = static function (string $message) use ($logFile): void {
    @file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
};

set_exception_handler(static function (Throwable $exception) use ($logger, $debug): void {
    $logger(sprintf(
        "%s: %s in %s:%d\n%s",
        $exception::class,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $message = $debug ? $exception->getMessage() : 'Terjadi kesalahan pada server.';

    if (Config::bool('app.debug', false)) {
        echo '<div style="font:13px/1.6 ui-monospace,monospace;background:#111;color:#eee;padding:24px;white-space:pre-wrap">';
        echo htmlspecialchars($exception::class . ': ' . $message, ENT_QUOTES) . "\n\n";
        echo htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES);
        echo '</div>';

        return;
    }

    $errorView = BASE_PATH . '/views/errors/500.php';
    if (is_file($errorView)) {
        include $errorView;
    } else {
        echo 'Terjadi kesalahan pada server.';
    }
});

set_error_handler(static function (int $severity, string $message, string $file, int $line) use ($logger): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    $logger(sprintf('PHP [%d] %s in %s:%d', $severity, $message, $file, $line));

    if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    return true;
});

register_shutdown_function(static function () use ($logger): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $logger(sprintf('FATAL %s in %s:%d', $error['message'], $error['file'], $error['line']));
    }
});

// ---- Database ---------------------------------------------------------
try {
    Database::connection();
} catch (Throwable $exception) {
    $logger('DB: ' . $exception->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $detail = Config::bool('app.debug', false)
        ? '<pre style="white-space:pre-wrap;opacity:.75">' . htmlspecialchars($exception->getMessage()) . '</pre>'
        : '';

    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Database belum siap</title></head><body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#0b1020;color:#e8ecf8;font:15px/1.7 system-ui,sans-serif">'
        . '<div style="max-width:520px;padding:32px;background:#111830;border:1px solid #26314f;border-radius:18px">'
        . '<h1 style="margin:0 0 12px;font-size:22px">Database belum siap</h1>'
        . '<p style="margin:0 0 10px;color:#a9b4d0">Aplikasi tidak bisa terhubung ke database. Periksa konfigurasi di file <code>.env</code>:</p>'
        . '<ul style="color:#a9b4d0;padding-left:20px"><li><code>DB_CONNECTION</code> = sqlite / mysql</li>'
        . '<li>Untuk SQLite pastikan folder <code>storage/</code> dapat ditulis (chmod 775)</li>'
        . '<li>Untuk MySQL isi <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code></li></ul>'
        . $detail
        . '</div></body></html>';

    exit;
}

// ---- Data global untuk semua view -------------------------------------
View::share('authUser', Auth::user());
View::share('isAdmin', Auth::isAdmin());
View::share('settings', Settings::all());
View::share('siteTitle', Settings::get('site_title', 'Portfolio'));
View::share('accent', accent_color());
View::share('registrationOpen', registration_open());
View::share('currentPath', current_path());
\App\Lang::boot();
View::share('locale', \App\Lang::locale());
View::share('profile', App\Models\Profile::get());

try {
    View::share('hasGallery', App\Models\Project::hasGallery());
} catch (Throwable) {
    View::share('hasGallery', false);
}
