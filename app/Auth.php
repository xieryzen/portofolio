<?php

declare(strict_types=1);

namespace App;

/**
 * Manajemen autentikasi & hak akses.
 */
final class Auth
{
    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    private static bool $resolved = false;

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }

        self::$resolved = true;

        $id = (int) ($_SESSION['_auth_id'] ?? 0);
        if ($id <= 0) {
            return self::$user = null;
        }

        // Sesi kedaluwarsa karena tidak aktif.
        $maxIdle = 60 * 60 * 6;
        $lastSeen = (int) ($_SESSION['_auth_seen'] ?? 0);
        if ($lastSeen > 0 && (time() - $lastSeen) > $maxIdle) {
            self::logout(false);

            return self::$user = null;
        }

        $statement = Database::connection()->prepare(
            'SELECT id, name, email, role, is_active, last_login_at, created_at FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            self::logout(false);

            return self::$user = null;
        }

        $_SESSION['_auth_seen'] = time();
        $_SESSION['_auth_role'] = $user['role'];

        return self::$user = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function guest(): bool
    {
        return !self::check();
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && in_array($user['role'], ['owner', 'admin'], true);
    }

    public static function isOwner(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === 'owner';
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['_auth_id'] = (int) $user['id'];
        $_SESSION['_auth_seen'] = time();
        $_SESSION['_auth_role'] = (string) $user['role'];
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));

        $statement = Database::connection()->prepare(
            'UPDATE users SET last_login_at = :now WHERE id = :id'
        );
        $statement->execute(['now' => date('Y-m-d H:i:s'), 'id' => (int) $user['id']]);

        self::$resolved = true;
        self::$user = $user;
    }

    public static function logout(bool $regenerate = true): void
    {
        self::$user = null;
        self::$resolved = true;

        unset($_SESSION['_auth_id'], $_SESSION['_auth_seen'], $_SESSION['_auth_role']);

        if ($regenerate && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (self::wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Silakan masuk terlebih dahulu.']);
                exit;
            }

            flash('info', 'Silakan masuk untuk melanjutkan.');
            redirect('/login?next=' . urlencode(current_path()));
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::isAdmin()) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Akses ditolak'], 'layouts/app');
            exit;
        }
    }

    public static function requireOwner(): void
    {
        self::requireLogin();

        if (!self::isOwner()) {
            http_response_code(403);
            View::render('errors/403', [
                'title' => 'Khusus owner',
                'message' => 'Halaman ini hanya dapat diakses oleh pemilik situs (owner).',
            ], 'layouts/app');
            exit;
        }
    }

    /**
     * Batasi percobaan login: maksimal 6 kegagalan dalam 10 menit.
     */
    public static function tooManyAttempts(string $email): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE email = :email AND attempted_at >= :since'
        );
        $statement->execute([
            'email' => mb_strtolower($email),
            'since' => date('Y-m-d H:i:s', time() - 600),
        ]);

        return (int) $statement->fetchColumn() >= 6;
    }

    public static function recordAttempt(string $email): void
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'INSERT INTO login_attempts (email, ip_address, attempted_at) VALUES (:email, :ip, :at)'
        );
        $statement->execute([
            'email' => mb_strtolower($email),
            'ip' => client_ip(),
            'at' => date('Y-m-d H:i:s'),
        ]);

        // Bersihkan catatan lama sesekali.
        if (random_int(1, 20) === 1) {
            $pdo->prepare('DELETE FROM login_attempts WHERE attempted_at < :since')
                ->execute(['since' => date('Y-m-d H:i:s', time() - 86400)]);
        }
    }

    public static function clearAttempts(string $email): void
    {
        Database::connection()
            ->prepare('DELETE FROM login_attempts WHERE email = :email')
            ->execute(['email' => mb_strtolower($email)]);
    }

    private static function wantsJson(): bool
    {
        return str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');
    }
}
