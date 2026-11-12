<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Artikel blog: publikasi, pencarian, kategori, dan artikel terkait.
 */
final class Post
{
    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginatePublic(int $page, int $perPage = 9, string $category = '', string $search = ''): array
    {
        $pdo = Database::connection();
        $where = ["status = 'published'", 'published_at IS NOT NULL', "published_at <= :now"];
        $params = ['now' => date('Y-m-d H:i:s')];

        if ($category !== '') {
            $where[] = 'category = :category';
            $params['category'] = $category;
        }

        if ($search !== '') {
            $where[] = '(title LIKE :q1 OR excerpt LIKE :q2 OR content LIKE :q3 OR tags LIKE :q4)';
            $like = '%' . $search . '%';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $count = $pdo->prepare('SELECT COUNT(*) FROM posts' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->prepare(
            'SELECT * FROM posts' . $whereSql . ' ORDER BY is_featured DESC, published_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute($params);

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginateAdmin(int $page, int $perPage = 10, string $status = '', string $search = ''): array
    {
        $pdo = Database::connection();
        $where = [];
        $params = [];

        if ($status !== '' && in_array($status, ['published', 'draft'], true)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($search !== '') {
            $where[] = '(title LIKE :q OR excerpt LIKE :q OR category LIKE :q OR tags LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $count = $pdo->prepare('SELECT COUNT(*) FROM posts' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->prepare(
            'SELECT * FROM posts' . $whereSql . ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
        );
        $statement->execute($params);

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySlug(string $slug, bool $onlyPublished = true): ?array
    {
        $sql = 'SELECT * FROM posts WHERE slug = :slug';
        $params = ['slug' => $slug];

        if ($onlyPublished) {
            $sql .= " AND status = 'published' AND published_at IS NOT NULL AND published_at <= :now";
            $params['now'] = date('Y-m-d H:i:s');
        }

        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        $post = $statement->fetch();

        return $post ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $post = $statement->fetch();

        return $post ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $status = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $publishedAt = $status === 'published'
            ? (string) ($data['published_at'] ?? '') ?: $now
            : null;

        $statement = Database::connection()->prepare(
            'INSERT INTO posts (title, slug, excerpt, content, category, tags, cover, status, is_featured,
                reading_minutes, views, published_at, created_at, updated_at)
             VALUES (:title, :slug, :excerpt, :content, :category, :tags, :cover, :status, :is_featured,
                :reading_minutes, 0, :published_at, :created_at, :updated_at)'
        );

        $statement->execute([
            'title' => $data['title'],
            'slug' => self::uniqueSlug((string) $data['title']),
            'excerpt' => $data['excerpt'] ?? '',
            'content' => $data['content'] ?? '',
            'category' => $data['category'] ?? 'Artikel',
            'tags' => $data['tags'] ?? '',
            'cover' => $data['cover'] ?? null,
            'status' => $status,
            'is_featured' => (int) ($data['is_featured'] ?? 0),
            'reading_minutes' => (int) ($data['reading_minutes'] ?? 0),
            'published_at' => $publishedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $existing = self::find($id);
        $status = ($data['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $publishedAt = $status === 'published'
            ? (string) ($data['published_at'] ?? '') ?: (string) ($existing['published_at'] ?? '') ?: date('Y-m-d H:i:s')
            : null;

        $statement = Database::connection()->prepare(
            'UPDATE posts SET title = :title, slug = :slug, excerpt = :excerpt, content = :content,
                category = :category, tags = :tags, status = :status, is_featured = :is_featured,
                reading_minutes = :reading_minutes, published_at = :published_at, updated_at = :updated_at
             WHERE id = :id'
        );

        $statement->execute([
            'title' => $data['title'],
            'slug' => self::uniqueSlug((string) $data['title'], $id),
            'excerpt' => $data['excerpt'] ?? '',
            'content' => $data['content'] ?? '',
            'category' => $data['category'] ?? 'Artikel',
            'tags' => $data['tags'] ?? '',
            'status' => $status,
            'is_featured' => (int) ($data['is_featured'] ?? 0),
            'reading_minutes' => (int) ($data['reading_minutes'] ?? 0),
            'published_at' => $publishedAt,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function setCover(int $id, ?string $cover): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE posts SET cover = :cover, updated_at = :updated WHERE id = :id'
        );
        $statement->execute(['cover' => $cover, 'updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM posts WHERE id = :id')->execute(['id' => $id]);
    }

    public static function incrementViews(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE posts SET views = views + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function latest(int $limit = 3): array
    {
        $statement = Database::connection()->prepare(
            "SELECT * FROM posts
             WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= :now
             ORDER BY published_at DESC, id DESC LIMIT " . max(1, min(500, $limit))
        );
        $statement->execute(['now' => date('Y-m-d H:i:s')]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function related(int $id, string $category, int $limit = 3): array
    {
        $statement = Database::connection()->prepare(
            "SELECT * FROM posts
             WHERE id <> :id AND status = 'published' AND published_at IS NOT NULL AND published_at <= :now
             ORDER BY CASE WHEN category = :category THEN 0 ELSE 1 END, published_at DESC
             LIMIT " . max(1, min(6, $limit))
        );
        $statement->execute([
            'id' => $id,
            'category' => $category,
            'now' => date('Y-m-d H:i:s'),
        ]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array{category: string, total: int}>
     */
    public static function categories(): array
    {
        $statement = Database::connection()->prepare(
            "SELECT category, COUNT(*) AS total FROM posts
             WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= :now
             GROUP BY category ORDER BY total DESC, category ASC"
        );
        $statement->execute(['now' => date('Y-m-d H:i:s')]);

        return $statement->fetchAll();
    }

    public static function hasPublished(): bool
    {
        try {
            $statement = Database::connection()->prepare(
                "SELECT COUNT(*) FROM posts WHERE status = 'published' AND published_at IS NOT NULL AND published_at <= :now"
            );
            $statement->execute(['now' => date('Y-m-d H:i:s')]);

            return (int) $statement->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function readingMinutes(string $content): int
    {
        $words = preg_split('~\s+~u', trim(strip_tags($content))) ?: [];
        $words = array_filter($words, static fn (string $word): bool => $word !== '');

        return max(1, (int) ceil(count($words) / 200));
    }

    public static function uniqueSlug(string $title, int $ignoreId = 0): string
    {
        $base = \slugify($title);
        $slug = $base;
        $index = 2;

        $statement = Database::connection()->prepare('SELECT COUNT(*) FROM posts WHERE slug = :slug AND id <> :id');

        while (true) {
            $statement->execute(['slug' => $slug, 'id' => $ignoreId]);
            if ((int) $statement->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $index++;
        }
    }
}
