<?php

declare(strict_types=1);

/**
 * Helper global yang dipakai di controller maupun view.
 * Semua fungsi diberi prefix nama yang jelas dan aman dipanggil berkali-kali.
 */

use App\Config;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);

        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Escape output HTML.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('nl2br_safe')) {
    function nl2br_safe(?string $text): string
    {
        return nl2br(e($text ?? ''));
    }
}

if (!function_exists('app_url')) {
    /**
     * Bangun URL absolut-path dengan dukungan sub-folder.
     */
    function app_url(string $path = '/'): string
    {
        $base = rtrim(app_base_url(), '/');
        $path = '/' . ltrim($path, '/');

        if ($path === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        return $base . $path;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string
    {
        $configured = Config::string('app.url');
        if ($configured !== '') {
            return $configured;
        }

        static $cached = null;
        if (is_string($cached)) {
            return $cached;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptName = str_replace('\\', '/', $script);
        $base = '';

        // public/index.php -> base adalah folder di atas /public
        if (str_contains($scriptName, '/public/')) {
            $base = substr($scriptName, 0, strpos($scriptName, '/public/'));
        } elseif (str_ends_with($scriptName, '/index.php')) {
            $base = substr($scriptName, 0, -strlen('/index.php'));
        }

        // Router script (php -S) memakai nama file lain.
        if ($base !== '' && !str_starts_with($scriptName, $base . '/')) {
            $base = '';
        }

        return $cached = $base;
    }
}

if (!function_exists('absolute_url')) {
    function absolute_url(string $path): string
    {
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $scheme = is_secure() ? 'https' : 'http';

        return $scheme . '://' . $host . $path;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $file = base_path('public/' . $relative);
        $version = is_file($file) ? (string) filemtime($file) : '1';

        return app_url('/' . $relative) . '?v=' . $version;
    }
}

if (!function_exists('social_url')) {
    /**
     * Normalisasi tautan sosial: nomor WhatsApp jadi wa.me, dan nilai tanpa
     * protokol otomatis diberi https://.
     */
    function social_url(string $key, string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if ($key === 'whatsapp') {
            $digits = preg_replace('~[^0-9]~', '', $value) ?? '';
            if ($digits !== '') {
                return 'https://wa.me/' . $digits;
            }
        }

        if (preg_match('~^https?://~i', $value)) {
            return $value;
        }

        if (str_starts_with($value, '//')) {
            return 'https:' . $value;
        }

        return 'https://' . ltrim($value, '/');
    }
}

if (!function_exists('media_url')) {
    /**
     * URL untuk file upload (dilayani lewat route /media/{file}).
     */
    function media_url(?string $file): ?string
    {
        $file = trim((string) $file);
        if ($file === '') {
            return null;
        }

        if (preg_match('~^https?://~i', $file)) {
            return $file;
        }

        return app_url('/media/' . rawurlencode(basename($file)));
    }
}

if (!function_exists('is_secure')) {
    function is_secure(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (!\App\Config::bool('app.trust_proxy', true)) {
            return false;
        }

        $proto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));

        return $proto === 'https';
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $ip = trim(explode(',', (string) $candidate)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = $_SESSION['_old'][$key] ?? $default;

        return is_string($old) ? trim($old) : $old;
    }
}

if (!function_exists('errors')) {
    /**
     * @return array<string, string>
     */
    function errors(): array
    {
        return $_SESSION['_errors'] ?? [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $key): ?string
    {
        return errors()[$key] ?? null;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('flash_messages')) {
    /**
     * @return list<array{type: string, message: string}>
     */
    function flash_messages(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $status = 302): never
    {
        $target = preg_match('~^https?://~i', $path) ? $path : app_url($path);
        if (!headers_sent()) {
            header('Location: ' . $target, true, $status);
        } else {
            echo '<script>location.href=' . json_encode($target) . ';</script>';
        }
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_valid')) {
    function csrf_valid(?string $token): bool
    {
        $session = $_SESSION['_csrf'] ?? '';

        return is_string($token) && $session !== '' && hash_equals($session, $token);
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = trim(strip_tags($text));
        $text = preg_replace('~[^\pL\pN]+~u', '-', $text) ?? '';
        $text = trim($text, '-');
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);

        return $text !== '' ? $text : 'project-' . substr(bin2hex(random_bytes(3)), 0, 5);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $limit = 150): string
    {
        $text = trim(preg_replace('~\s+~u', ' ', strip_tags((string) $text)) ?? '');
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
            return rtrim(mb_substr($text, 0, $limit, 'UTF-8')) . '…';
        }

        return $text;
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd F Y'): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '-';
        }

        $english = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $indonesia = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $formatted = date($format, $timestamp);

        return str_replace($english, $indonesia, $formatted);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '-';
        }

        $diff = time() - $timestamp;
        if ($diff < 60) {
            return 'baru saja';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' menit lalu';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' jam lalu';
        }
        if ($diff < 2592000) {
            return floor($diff / 86400) . ' hari lalu';
        }

        return format_date($datetime, 'd M Y');
    }
}

if (!function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        return \App\Settings::get($key, $default);
    }
}

if (!function_exists('registration_open')) {
    function registration_open(): bool
    {
        return Config::bool('registration', true) && setting('allow_registration', '1') === '1';
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int|float $bytes): string
    {
        $bytes = max(0, (float) $bytes);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        $decimals = $index === 0 ? 0 : ($bytes < 10 ? 1 : 0);

        return number_format($bytes, $decimals, ',', '.') . ' ' . $units[$index];
    }
}

if (!function_exists('release_file_label')) {
    /**
     * Label ekstensi file rilis untuk ditampilkan di UI.
     */
    function release_file_label(?string $originalName, ?string $fileName = null): string
    {
        $source = $originalName ?: $fileName;

        if (!$source) {
            return 'TANPA FILE';
        }

        $extension = strtolower((string) pathinfo($source, PATHINFO_EXTENSION));

        return $extension !== '' ? strtoupper($extension) : 'FILE';
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('~\s+~u', trim($name)) ?: [];
        $letters = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $letters .= mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8');
            }
        }

        return $letters !== '' ? $letters : 'P';
    }
}

if (!function_exists('accent_color')) {
    function accent_color(): string
    {
        $color = (string) setting('accent_color', '#6d8bff');

        return preg_match('~^#[0-9a-fA-F]{6}$~', $color) ? $color : '#6d8bff';
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = app_base_url();

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('is_active_path')) {
    function is_active_path(string $prefix, bool $exact = false): bool
    {
        $current = current_path();
        if ($exact) {
            return $current === $prefix;
        }

        return $current === $prefix || str_starts_with($current, rtrim($prefix, '/') . '/');
    }
}

if (!function_exists('query_int')) {
    function query_int(string $key, int $default = 1, int $min = 1): int
    {
        $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);

        return is_int($value) && $value >= $min ? $value : $default;
    }
}

if (!function_exists('svg_icon')) {
    /**
     * Icon SVG inline (stroke currentColor) — tanpa dependensi eksternal.
     */
    function svg_icon(string $name, string $class = 'icon'): string
    {
        $paths = [
            'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
            'arrow-up-right' => '<path d="M7 17 17 7M9 7h8v8"/>',
            'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'external' => '<path d="M14 3h7v7M21 3l-9 9M10 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/>',
            'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
            'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2.2 2A16 16 0 0 1 3 6.2 2 2 0 0 1 5 4Z"/>',
            'map-pin' => '<path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
            'github' => '<path d="M9 19c-4 1.5-4-2.5-6-3m12 6v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.3 4.3 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 3.8 5.4 4.1 5.4 4.1a4.3 4.3 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 10.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V22"/>',
            'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6Z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
            'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/>',
            'x' => '<path d="M4 3h4l5 7 5-7h3l-6.5 9L21 21h-4l-5-7-5 7H4l6.5-9Z"/>',
            'website' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 4 5.6 4 9s-1.5 6.4-4 9c-2.5-2.6-4-5.6-4-9s1.5-6.4 4-9Z"/>',
            'whatsapp' => '<path d="M21 11.5a8.4 8.4 0 0 1-12.5 7.4L3 21l2.1-5.4A8.4 8.4 0 1 1 21 11.5Z"/><path d="M8.5 8.8c.3-.6.7-.6 1-.6h.6c.2 0 .4.1.5.4l.7 1.6c.1.3 0 .5-.1.7l-.5.6c-.1.2-.2.4 0 .7a6 6 0 0 0 2.6 2.3c.3.1.5.1.7-.1l.6-.7c.2-.2.4-.2.6-.1l1.6.8c.2.1.3.3.3.5 0 .7-.3 1.4-.7 1.7-.4.3-1 .6-2 .5-1.5-.1-3.4-1.1-4.8-2.5-1.4-1.4-2.4-3.3-2.5-4.8-.1-1 .2-1.6.5-2Z"/>',
            'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
            'moon' => '<path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.6 6.6 0 0 0 9.8 9.8Z"/>',
            'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
            'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
            'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
            'database' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
            'upload' => '<path d="M12 16V4M7 9l5-5 5 5M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/>',
            'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3.1-6 7-6s7 2 7 6"/><path d="M17 4.5a4 4 0 0 1 0 7.5M18 15c2.5.6 4 2.6 4 6"/>',
            'message' => '<path d="M21 15a3 3 0 0 1-3 3H8l-5 4V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3Z"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
            'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
            'edit' => '<path d="M4 20h4l10-10a2.8 2.8 0 0 0-4-4L4 16Z"/><path d="m13.5 6.5 4 4"/>',
            'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
            'star' => '<path d="m12 3 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9Z"/>',
            'image' => '<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m4 17 5-5 4 4 3-3 4 4"/>',
            'check' => '<path d="m4 12 5 5L20 6"/>',
            'alert' => '<path d="M12 3 2.5 20h19Z"/><path d="M12 9v5M12 17.5h.01"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5h.01"/>',
            'lock' => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            'sparkles' => '<path d="m12 3 1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8Z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9Z"/>',
            'download' => '<path d="M12 3v12M7 11l5 5 5-5M4 21h16"/>',
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'filter' => '<path d="M3 5h18l-7 8v6l-4-2v-4Z"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
            'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 12h18"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
            'package' => '<path d="m21 8-9-5-9 5v8l9 5 9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
            'file-text' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        ];

        $inner = $paths[$name] ?? $paths['sparkles'];

        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $inner . '</svg>';
    }
}
