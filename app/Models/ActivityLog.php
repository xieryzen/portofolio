<?php

declare(strict_types=1);

namespace App\Models;

use App\Auth;
use App\Database;

final class ActivityLog
{
    public static function record(string $action, string $description = '', ?int $userId = null, ?string $actor = null): void
    {
        try {
            $user = Auth::user();

            if ($userId === null && $user !== null) {
                $userId = (int) $user['id'];
            }

            if ($actor === null) {
                $actor = $user !== null ? (string) $user['email'] : '';
            }

            $statement = Database::connection()->prepare(
                'INSERT INTO activity_logs (user_id, actor, action, description, ip_address, user_agent, created_at)
                 VALUES (:user_id, :actor, :action, :description, :ip, :agent, :created_at)'
            );

            $statement->execute([
                'user_id' => $userId,
                'actor' => mb_substr($actor, 0, 190, 'UTF-8'),
                'action' => mb_substr($action, 0, 60, 'UTF-8'),
                'description' => mb_substr($description, 0, 255, 'UTF-8'),
                'ip' => function_exists('client_ip') ? client_ip() : '',
                'agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Bersihkan entri lama sesekali agar tabel tidak tumbuh tanpa batas.
            if (random_int(1, 200) === 1) {
                $cutoff = date('Y-m-d H:i:s', time() - 60 * 60 * 24 * 180);
                $prune = Database::connection()->prepare('DELETE FROM activity_logs WHERE created_at < :cutoff');
                $prune->execute(['cutoff' => $cutoff]);
            }
        } catch (\Throwable) {
            // Log aktivitas tidak boleh menggagalkan aksi utama.
        }
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginate(int $page = 1, int $perPage = 40): array
    {
        $pdo = Database::connection();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM activity_logs')->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->query(
            'SELECT * FROM activity_logs ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }
}
