<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Komentar artikel dengan moderasi (default: menunggu persetujuan).
 */
final class Comment
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function approvedFor(int $postId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM post_comments WHERE post_id = :post AND is_approved = 1 ORDER BY created_at ASC, id ASC'
        );
        $statement->execute(['post' => $postId]);

        return $statement->fetchAll();
    }

    public static function create(int $postId, string $name, string $email, string $body): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO post_comments (post_id, name, email, body, is_approved, ip_address, created_at)
             VALUES (:post, :name, :email, :body, 0, :ip, :created_at)'
        );
        $statement->execute([
            'post' => $postId,
            'name' => $name,
            'email' => $email,
            'body' => $body,
            'ip' => function_exists('client_ip') ? client_ip() : '',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginate(string $status = 'pending', int $page = 1, int $perPage = 20): array
    {
        $pdo = Database::connection();
        $where = '';
        $params = [];

        if ($status === 'pending') {
            $where = ' WHERE c.is_approved = 0';
        } elseif ($status === 'approved') {
            $where = ' WHERE c.is_approved = 1';
        }

        $count = $pdo->prepare('SELECT COUNT(*) FROM post_comments c' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->prepare(
            'SELECT c.*, p.title AS post_title, p.slug AS post_slug
             FROM post_comments c LEFT JOIN posts p ON p.id = c.post_id' . $where .
            ' ORDER BY c.created_at DESC, c.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute($params);

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    public static function approve(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE post_comments SET is_approved = 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()
            ->prepare('DELETE FROM post_comments WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function pendingCount(): int
    {
        try {
            return (int) Database::connection()
                ->query('SELECT COUNT(*) FROM post_comments WHERE is_approved = 0')
                ->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function countForPost(int $postId): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM post_comments WHERE post_id = :post AND is_approved = 1'
        );
        $statement->execute(['post' => $postId]);

        return (int) $statement->fetchColumn();
    }
}
