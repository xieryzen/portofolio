<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;

final class Project
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function published(int $limit = 50): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM projects WHERE status = :status ORDER BY is_featured DESC, sort_order ASC, id DESC LIMIT ' . $limit
        );
        $statement->execute(['status' => 'published']);

        return $statement->fetchAll();
    }

    /**
     * Cari project yang sudah terbit berdasarkan judul, ringkasan, kategori, atau tech stack.
     *
     * @return list<array<string, mixed>>
     */
    public static function searchPublished(string $term, int $limit = 60): array
    {
        $term = trim($term);

        if ($term === '') {
            return self::published($limit);
        }

        $like = '%' . $term . '%';
        $statement = Database::connection()->prepare(
            'SELECT * FROM projects
             WHERE status = :status
               AND (title LIKE :q1 OR summary LIKE :q2 OR description LIKE :q3 OR category LIKE :q4 OR tech_stack LIKE :q5)
             ORDER BY is_featured DESC, sort_order ASC, id DESC
             LIMIT ' . max(1, min($limit, 200))
        );

        $statement->execute([
            'status' => 'published',
            'q1' => $like,
            'q2' => $like,
            'q3' => $like,
            'q4' => $like,
            'q5' => $like,
        ]);

        return $statement->fetchAll();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function featured(int $limit = 3): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM projects WHERE status = :status AND is_featured = 1 ORDER BY sort_order ASC, id DESC LIMIT ' . $limit
        );
        $statement->execute(['status' => 'published']);

        return $statement->fetchAll();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, pages: int, page: int}
     */
    public static function paginate(int $page, int $perPage = 10, ?string $status = null, string $search = ''): array
    {
        $pdo = Database::connection();
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if ($search !== '') {
            $where[] = '(title LIKE :search OR summary LIKE :search OR tech_stack LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $count = $pdo->prepare('SELECT COUNT(*) FROM projects' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->prepare(
            'SELECT * FROM projects' . $whereSql . ' ORDER BY sort_order ASC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset
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
        $sql = 'SELECT * FROM projects WHERE slug = :slug';
        if ($onlyPublished) {
            $sql .= ' AND status = :status';
        }

        $statement = Database::connection()->prepare($sql . ' LIMIT 1');
        $params = ['slug' => $slug];
        if ($onlyPublished) {
            $params['status'] = 'published';
        }
        $statement->execute($params);
        $project = $statement->fetch();

        return $project ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $project = $statement->fetch();

        return $project ?: null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO projects (title, slug, summary, description, category, client, role, year, tech_stack,
                case_challenge, case_solution, case_result, case_metrics,
                project_url, repo_url, cover, is_featured, status, sort_order, created_at, updated_at)
             VALUES (:title, :slug, :summary, :description, :category, :client, :role, :year, :tech_stack,
                :case_challenge, :case_solution, :case_result, :case_metrics,
                :project_url, :repo_url, :cover, :is_featured, :status, :sort_order, :created_at, :updated_at)'
        );

        $now = date('Y-m-d H:i:s');
        $statement->execute([
            'title' => $data['title'],
            'slug' => self::uniqueSlug((string) $data['title'], (int) ($data['id'] ?? 0)),
            'summary' => $data['summary'] ?? '',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? 'Web App',
            'client' => $data['client'] ?? '',
            'role' => $data['role'] ?? '',
            'year' => $data['year'] ?? '',
            'tech_stack' => $data['tech_stack'] ?? '',
            'case_challenge' => $data['case_challenge'] ?? '',
            'case_solution' => $data['case_solution'] ?? '',
            'case_result' => $data['case_result'] ?? '',
            'case_metrics' => $data['case_metrics'] ?? '',
            'project_url' => $data['project_url'] ?? '',
            'repo_url' => $data['repo_url'] ?? '',
            'cover' => $data['cover'] ?? null,
            'is_featured' => (int) ($data['is_featured'] ?? 0),
            'status' => $data['status'] ?? 'published',
            'sort_order' => (int) ($data['sort_order'] ?? self::nextSortOrder()),
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
        $statement = Database::connection()->prepare(
            'UPDATE projects SET title = :title, slug = :slug, summary = :summary, description = :description,
                category = :category, client = :client, role = :role, year = :year, tech_stack = :tech_stack,
                case_challenge = :case_challenge, case_solution = :case_solution, case_result = :case_result,
                case_metrics = :case_metrics,
                project_url = :project_url, repo_url = :repo_url, is_featured = :is_featured, status = :status,
                sort_order = :sort_order, updated_at = :updated_at
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'title' => $data['title'],
            'slug' => self::uniqueSlug((string) $data['title'], $id),
            'summary' => $data['summary'] ?? '',
            'description' => $data['description'] ?? '',
            'category' => $data['category'] ?? 'Web App',
            'client' => $data['client'] ?? '',
            'role' => $data['role'] ?? '',
            'year' => $data['year'] ?? '',
            'tech_stack' => $data['tech_stack'] ?? '',
            'case_challenge' => $data['case_challenge'] ?? '',
            'case_solution' => $data['case_solution'] ?? '',
            'case_result' => $data['case_result'] ?? '',
            'case_metrics' => $data['case_metrics'] ?? '',
            'project_url' => $data['project_url'] ?? '',
            'repo_url' => $data['repo_url'] ?? '',
            'is_featured' => (int) ($data['is_featured'] ?? 0),
            'status' => $data['status'] ?? 'published',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setCover(int $id, ?string $cover): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE projects SET cover = :cover, updated_at = :updated WHERE id = :id'
        );
        $statement->execute(['cover' => $cover, 'updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE projects SET status = :status, updated_at = :updated WHERE id = :id'
        );
        $statement->execute(['status' => $status, 'updated' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::connection();

        foreach (self::images($id) as $image) {
            \App\Uploader::delete((string) $image['file_name']);
        }

        Release::deleteByProject($id);

        $pdo->prepare('DELETE FROM project_images WHERE project_id = :id')->execute(['id' => $id]);
        $pdo->prepare('DELETE FROM projects WHERE id = :id')->execute(['id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function images(int $projectId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM project_images WHERE project_id = :id ORDER BY sort_order ASC, id ASC'
        );
        $statement->execute(['id' => $projectId]);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findImage(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM project_images WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $image = $statement->fetch();

        return $image ?: null;
    }

    public static function addImage(int $projectId, string $fileName, string $caption = ''): int
    {
        $pdo = Database::connection();
        $order = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM project_images WHERE project_id = :id');
        $order->execute(['id' => $projectId]);
        $next = (int) $order->fetchColumn() + 1;

        $statement = $pdo->prepare(
            'INSERT INTO project_images (project_id, file_name, caption, sort_order, created_at)
             VALUES (:project_id, :file_name, :caption, :sort_order, :created_at)'
        );
        $statement->execute([
            'project_id' => $projectId,
            'file_name' => $fileName,
            'caption' => $caption,
            'sort_order' => $next,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateImageCaption(int $id, string $caption): void
    {
        Database::connection()
            ->prepare('UPDATE project_images SET caption = :caption WHERE id = :id')
            ->execute(['caption' => $caption, 'id' => $id]);
    }

    public static function deleteImage(int $id): void
    {
        $image = self::findImage($id);
        if (!$image) {
            return;
        }

        \App\Uploader::delete((string) $image['file_name']);
        Database::connection()->prepare('DELETE FROM project_images WHERE id = :id')->execute(['id' => $id]);
    }

    public static function incrementViews(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE projects SET views = views + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function hasGallery(): bool
    {
        $statement = Database::connection()->query(
            "SELECT 1 FROM project_images i
             INNER JOIN projects p ON p.id = i.project_id
             WHERE p.status = 'published' LIMIT 1"
        );

        return (bool) $statement->fetchColumn();
    }

    /**
     * Gambar dokumentasi dari project yang sudah dipublikasikan.
     *
     * @return list<array<string, mixed>>
     */
    public static function galleryImages(int $limit = 8): array
    {
        $statement = Database::connection()->prepare(
            "SELECT i.*, p.title AS project_title, p.slug AS project_slug
             FROM project_images i
             INNER JOIN projects p ON p.id = i.project_id
             WHERE p.status = 'published'
             ORDER BY p.is_featured DESC, i.sort_order ASC, i.id DESC
             LIMIT " . $limit
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    /**
     * @return array<string, int>
     */
    public static function stats(): array
    {
        $pdo = Database::connection();
        $row = $pdo->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft,
                COALESCE(SUM(views), 0) AS views
             FROM projects"
        )->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
            'draft' => (int) ($row['draft'] ?? 0),
            'views' => (int) ($row['views'] ?? 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function related(int $excludeId, int $limit = 3): array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM projects WHERE status = :status AND id <> :id ORDER BY is_featured DESC, id DESC LIMIT ' . $limit
        );
        $statement->execute(['status' => 'published', 'id' => $excludeId]);

        return $statement->fetchAll();
    }

    private static function nextSortOrder(): int
    {
        $value = Database::connection()->query('SELECT COALESCE(MAX(sort_order), 0) FROM projects')->fetchColumn();

        return (int) $value + 1;
    }

    public static function uniqueSlug(string $title, int $ignoreId = 0): string
    {
        $base = \slugify($title);
        $slug = $base;
        $i = 2;
        $pdo = Database::connection();

        $statement = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE slug = :slug AND id <> :id');
        while (true) {
            $statement->execute(['slug' => $slug, 'id' => $ignoreId]);
            if ((int) $statement->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
