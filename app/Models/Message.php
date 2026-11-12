<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class Message
{
    public static function create(string $name, string $email, string $subject, string $body, string $ip = ''): int
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'INSERT INTO messages (name, email, subject, body, is_read, ip_address, created_at)
             VALUES (:name, :email, :subject, :body, 0, :ip, :created_at)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'body' => $body,
            'ip' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(int $limit = 100): array
    {
        return Database::connection()
            ->query('SELECT * FROM messages ORDER BY is_read ASC, id DESC LIMIT ' . $limit)
            ->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM messages WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $message = $statement->fetch();

        return $message ?: null;
    }

    public static function toggleRead(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE messages SET is_read = CASE WHEN is_read = 1 THEN 0 ELSE 1 END WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function markRead(int $id): void
    {
        Database::connection()->prepare('UPDATE messages SET is_read = 1 WHERE id = :id')->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM messages WHERE id = :id')->execute(['id' => $id]);
    }

    public static function unreadCount(): int
    {
        return (int) Database::connection()->query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
    }
}
