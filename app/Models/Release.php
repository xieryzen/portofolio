<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Uploader;

/**
 * Rilis / unduhan per project.
 */
final class Release
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function forProject(int $projectId, bool $onlyPublished = false): array
    {
        $sql = 'SELECT * FROM project_releases WHERE project_id = :project_id';
        if ($onlyPublished) {
            $sql .= ' AND is_published = 1';
        }

        $statement = Database::connection()->prepare(
            $sql . ' ORDER BY released_at DESC, id DESC'
        );
        $statement->execute(['project_id' => $projectId]);

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT r.*, p.title AS project_title, p.slug AS project_slug, p.status AS project_status
             FROM project_releases r
             INNER JOIN projects p ON p.id = r.project_id
             WHERE r.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $release = $statement->fetch();

        return $release ?: null;
    }

    public static function latestPublished(int $projectId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT * FROM project_releases
             WHERE project_id = :project_id AND is_published = 1
             ORDER BY released_at DESC, id DESC LIMIT 1'
        );
        $statement->execute(['project_id' => $projectId]);
        $release = $statement->fetch();

        return $release ?: null;
    }

    public static function countPublished(int $projectId): int
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM project_releases WHERE project_id = :project_id AND is_published = 1'
        );
        $statement->execute(['project_id' => $projectId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(int $projectId, array $data): int
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');

        $statement = $pdo->prepare(
            'INSERT INTO project_releases (project_id, version, title, notes, file_name, original_name,
                file_size, is_published, downloads, released_at, created_at, updated_at)
             VALUES (:project_id, :version, :title, :notes, :file_name, :original_name,
                :file_size, :is_published, 0, :released_at, :created_at, :updated_at)'
        );

        $statement->execute([
            'project_id' => $projectId,
            'version' => $data['version'],
            'title' => $data['title'] ?? '',
            'notes' => $data['notes'] ?? '',
            'file_name' => $data['file_name'] ?? null,
            'original_name' => $data['original_name'] ?? null,
            'file_size' => (int) ($data['file_size'] ?? 0),
            'is_published' => (int) ($data['is_published'] ?? 1),
            'released_at' => $data['released_at'] ?? date('Y-m-d'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE project_releases SET version = :version, title = :title, notes = :notes,
                is_published = :is_published, released_at = :released_at, updated_at = :updated_at
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'version' => $data['version'],
            'title' => $data['title'] ?? '',
            'notes' => $data['notes'] ?? '',
            'is_published' => (int) ($data['is_published'] ?? 1),
            'released_at' => $data['released_at'] ?? date('Y-m-d'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function replaceFile(int $id, string $fileName, string $originalName, int $size): void
    {
        $release = self::find($id);
        if (!$release) {
            return;
        }

        $old = $release['file_name'] ?? null;

        Database::connection()->prepare(
            'UPDATE project_releases SET file_name = :file_name, original_name = :original_name,
                file_size = :file_size, updated_at = :updated_at WHERE id = :id'
        )->execute([
            'file_name' => $fileName,
            'original_name' => $originalName,
            'file_size' => $size,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);

        if ($old && $old !== $fileName) {
            Uploader::deleteRelease((string) $old);
        }
    }

    public static function removeFile(int $id): void
    {
        $release = self::find($id);
        if (!$release || !$release['file_name']) {
            return;
        }

        Uploader::deleteRelease((string) $release['file_name']);

        Database::connection()->prepare(
            'UPDATE project_releases SET file_name = NULL, original_name = NULL, file_size = 0,
                updated_at = :updated_at WHERE id = :id'
        )->execute(['updated_at' => date('Y-m-d H:i:s'), 'id' => $id]);
    }

    public static function setPublished(int $id, bool $published): void
    {
        Database::connection()->prepare(
            'UPDATE project_releases SET is_published = :published, updated_at = :updated WHERE id = :id'
        )->execute([
            'published' => $published ? 1 : 0,
            'updated' => date('Y-m-d H:i:s'),
            'id' => $id,
        ]);
    }

    public static function incrementDownloads(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE project_releases SET downloads = downloads + 1 WHERE id = :id')
            ->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $release = self::find($id);
        if (!$release) {
            return;
        }

        if (!empty($release['file_name'])) {
            Uploader::deleteRelease((string) $release['file_name']);
        }

        Database::connection()->prepare('DELETE FROM project_releases WHERE id = :id')->execute(['id' => $id]);
    }

    public static function deleteByProject(int $projectId): void
    {
        foreach (self::forProject($projectId) as $release) {
            self::delete((int) $release['id']);
        }
    }

    public static function versionExists(int $projectId, string $version, int $exceptId = 0): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*) FROM project_releases
             WHERE project_id = :project_id AND LOWER(version) = LOWER(:version) AND id <> :id'
        );
        $statement->execute([
            'project_id' => $projectId,
            'version' => trim($version),
            'id' => $exceptId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * @return array{total: int, downloads: int, published: int}
     */
    public static function stats(): array
    {
        $row = Database::connection()->query(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(downloads), 0) AS downloads,
                    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) AS published
             FROM project_releases'
        )->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'downloads' => (int) ($row['downloads'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
        ];
    }
}
