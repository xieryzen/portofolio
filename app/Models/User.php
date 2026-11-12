<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function saveTotpSecret(int $id, ?string $secret): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET totp_secret = :secret, totp_enabled_at = NULL, totp_recovery = NULL, updated_at = :updated WHERE id = :id'
        );
        $statement->execute([
            'secret' => $secret,
            'updated' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function enableTotp(int $id, string $secret, string $recoveryJson): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET totp_secret = :secret, totp_enabled_at = :enabled, totp_recovery = :recovery, updated_at = :updated WHERE id = :id'
        );
        $statement->execute([
            'secret' => $secret,
            'enabled' => date('Y-m-d H:i:s'),
            'recovery' => $recoveryJson,
            'updated' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function disableTotp(int $id): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET totp_secret = NULL, totp_enabled_at = NULL, totp_recovery = NULL, updated_at = :updated WHERE id = :id'
        );
        $statement->execute(['updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function setTotpRecovery(int $id, string $recoveryJson): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET totp_recovery = :recovery, updated_at = :updated WHERE id = :id'
        );
        $statement->execute(['recovery' => $recoveryJson, 'updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginate(int $page, int $perPage = 12, string $search = ''): array
    {
        $pdo = Database::connection();
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = ' WHERE name LIKE :search OR email LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $count = $pdo->prepare('SELECT COUNT(*) FROM users' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->prepare(
            'SELECT id, name, email, role, is_active, last_login_at, created_at FROM users'
            . $where . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute($params);

        return ['items' => $statement->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function create(string $name, string $email, string $password, string $role = 'user'): int
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');

        $statement = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
             VALUES (:name, :email, :hash, :role, 1, :created, :updated)'
        );
        $statement->execute([
            'name' => $name,
            'email' => mb_strtolower(trim($email)),
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'created' => $now,
            'updated' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::connection()
            ->prepare('UPDATE users SET password_hash = :hash, updated_at = :updated WHERE id = :id')
            ->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]);
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        Database::connection()
            ->prepare('UPDATE users SET name = :name, email = :email, updated_at = :updated WHERE id = :id')
            ->execute([
                'name' => $name,
                'email' => mb_strtolower(trim($email)),
                'updated' => date('Y-m-d H:i:s'),
                'id' => $id,
            ]);
    }

    public static function setRole(int $id, string $role): void
    {
        Database::connection()
            ->prepare('UPDATE users SET role = :role, updated_at = :updated WHERE id = :id')
            ->execute(['role' => $role, 'updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::connection()
            ->prepare('UPDATE users SET is_active = :active, updated_at = :updated WHERE id = :id')
            ->execute(['active' => $active ? 1 : 0, 'updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * @return array<string, int>
     */
    public static function stats(): array
    {
        $row = Database::connection()->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN role IN ('owner','admin') THEN 1 ELSE 0 END) AS admins,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive
             FROM users"
        )->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'admins' => (int) ($row['admins'] ?? 0),
            'inactive' => (int) ($row['inactive'] ?? 0),
        ];
    }
}
