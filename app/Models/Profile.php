<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class Profile
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $statement = Database::connection()->query('SELECT * FROM profiles ORDER BY id ASC LIMIT 1');
        $profile = $statement->fetch();

        return $profile ?: self::defaults();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(array $data): void
    {
        $profile = self::get();
        $pdo = Database::connection();

        if (!isset($profile['id'])) {
            $statement = $pdo->prepare(
                'INSERT INTO profiles (full_name, headline, tagline, bio, updated_at) VALUES (:name, :headline, :tagline, :bio, :updated)'
            );
            $statement->execute([
                'name' => $data['full_name'] ?? 'Nama Anda',
                'headline' => $data['headline'] ?? '',
                'tagline' => $data['tagline'] ?? '',
                'bio' => $data['bio'] ?? '',
                'updated' => date('Y-m-d H:i:s'),
            ]);

            return;
        }

        $statement = $pdo->prepare(
            'UPDATE profiles SET full_name = :full_name, headline = :headline, tagline = :tagline, bio = :bio,
                location = :location, public_email = :public_email, phone = :phone, availability = :availability,
                years_experience = :years_experience, projects_completed = :projects_completed,
                clients_count = :clients_count, github = :github, linkedin = :linkedin, instagram = :instagram,
                x = :x, website = :website, whatsapp = :whatsapp, updated_at = :updated_at
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $profile['id'],
            'full_name' => $data['full_name'] ?? '',
            'headline' => $data['headline'] ?? '',
            'tagline' => $data['tagline'] ?? '',
            'bio' => $data['bio'] ?? '',
            'location' => $data['location'] ?? '',
            'public_email' => $data['public_email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'availability' => $data['availability'] ?? '',
            'years_experience' => (int) ($data['years_experience'] ?? 0),
            'projects_completed' => (int) ($data['projects_completed'] ?? 0),
            'clients_count' => (int) ($data['clients_count'] ?? 0),
            'github' => $data['github'] ?? '',
            'linkedin' => $data['linkedin'] ?? '',
            'instagram' => $data['instagram'] ?? '',
            'x' => $data['x'] ?? '',
            'website' => $data['website'] ?? '',
            'whatsapp' => $data['whatsapp'] ?? '',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setImage(string $type, ?string $fileName): void
    {
        $column = $type === 'cover' ? 'cover' : 'avatar';
        $profile = self::get();

        $statement = Database::connection()->prepare(
            "UPDATE profiles SET $column = :file, updated_at = :updated WHERE id = :id"
        );
        $statement->execute([
            'file' => $fileName,
            'updated' => date('Y-m-d H:i:s'),
            'id' => $profile['id'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function defaults(): array
    {
        return [
            'id' => null,
            'full_name' => 'Nama Anda',
            'headline' => '',
            'tagline' => '',
            'bio' => '',
            'location' => '',
            'public_email' => '',
            'phone' => '',
            'availability' => '',
            'avatar' => null,
            'cover' => null,
            'years_experience' => 0,
            'projects_completed' => 0,
            'clients_count' => 0,
            'github' => '',
            'linkedin' => '',
            'instagram' => '',
            'x' => '',
            'website' => '',
            'whatsapp' => '',
        ];
    }
}
