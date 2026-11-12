<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class Skill
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM skills ORDER BY sort_order ASC, id ASC')
            ->fetchAll();
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::all() as $skill) {
            $groups[(string) $skill['category']][] = $skill;
        }

        return $groups;
    }

    public static function create(string $name, string $category, int $level): void
    {
        $pdo = Database::connection();
        $order = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM skills')->fetchColumn() + 1;

        $statement = $pdo->prepare(
            'INSERT INTO skills (name, category, level, sort_order) VALUES (:name, :category, :level, :sort_order)'
        );
        $statement->execute([
            'name' => $name,
            'category' => $category,
            'level' => max(0, min(100, $level)),
            'sort_order' => $order,
        ]);
    }

    public static function update(int $id, string $name, string $category, int $level): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE skills SET name = :name, category = :category, level = :level WHERE id = :id'
        );
        $statement->execute([
            'name' => $name,
            'category' => $category,
            'level' => max(0, min(100, $level)),
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM skills WHERE id = :id')->execute(['id' => $id]);
    }
}
