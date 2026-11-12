<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Koneksi database + migrasi skema otomatis.
 * Mendukung SQLite (default) dan MySQL/MariaDB.
 */
final class Database
{
    private static ?PDO $pdo = null;

    private const SCHEMA_VERSION = '5';

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = Config::string('db.connection', 'sqlite');
        $basePath = base_path();

        try {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                    Config::string('db.host', '127.0.0.1'),
                    Config::int('db.port', 3306),
                    Config::string('db.name', 'portfolio')
                );
                $pdo = new PDO($dsn, Config::string('db.username'), Config::string('db.password'), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$pdo = $pdo;
            } else {
                $database = Config::string('db.database', 'storage/database.sqlite');
                $path = str_starts_with($database, '/') ? $database : $basePath . '/' . ltrim($database, '/');

                $directory = dirname($path);
                if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                    throw new RuntimeException('Tidak bisa membuat folder database: ' . $directory);
                }

                $pdo = new PDO('sqlite:' . $path, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec('PRAGMA journal_mode = WAL');
                $pdo->exec('PRAGMA busy_timeout = 5000');
                $pdo->exec('PRAGMA foreign_keys = ON');
                self::$pdo = $pdo;
            }

            self::$pdo->query('SELECT 1');
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Koneksi database gagal: ' . $exception->getMessage() . '. Periksa konfigurasi DB_* pada file .env',
                0,
                $exception
            );
        }

        self::migrate();

        return self::$pdo;
    }

    public static function driver(): string
    {
        return (string) self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    /**
     * Jalankan migrasi hanya jika versi skema berubah.
     */
    public static function migrate(bool $force = false): void
    {
        $marker = base_path('storage/.schema_version');

        if (!$force && is_file($marker) && trim((string) file_get_contents($marker)) === self::SCHEMA_VERSION) {
            return;
        }

        $pdo = self::$pdo;
        if (!$pdo instanceof PDO) {
            $pdo = self::rawConnection();
            self::$pdo = $pdo;
        }

        $mysql = self::driver() === 'mysql';
        $id = $mysql ? 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $suffix = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

        $tables = [];

        $tables[] = "CREATE TABLE IF NOT EXISTS users (
            id $id,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at VARCHAR(25) NULL,
            totp_secret VARCHAR(64) NULL,
            totp_enabled_at VARCHAR(25) NULL,
            totp_recovery TEXT NULL,
            created_at VARCHAR(25) NOT NULL,
            updated_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS profiles (
            id $id,
            full_name VARCHAR(150) NOT NULL DEFAULT '',
            headline VARCHAR(200) NOT NULL DEFAULT '',
            tagline VARCHAR(300) NOT NULL DEFAULT '',
            bio TEXT NULL,
            location VARCHAR(150) NOT NULL DEFAULT '',
            public_email VARCHAR(190) NOT NULL DEFAULT '',
            phone VARCHAR(60) NOT NULL DEFAULT '',
            availability VARCHAR(120) NOT NULL DEFAULT '',
            avatar VARCHAR(190) NULL,
            cover VARCHAR(190) NULL,
            years_experience INT NOT NULL DEFAULT 0,
            projects_completed INT NOT NULL DEFAULT 0,
            clients_count INT NOT NULL DEFAULT 0,
            github VARCHAR(200) NOT NULL DEFAULT '',
            linkedin VARCHAR(200) NOT NULL DEFAULT '',
            instagram VARCHAR(200) NOT NULL DEFAULT '',
            x VARCHAR(200) NOT NULL DEFAULT '',
            website VARCHAR(200) NOT NULL DEFAULT '',
            whatsapp VARCHAR(60) NOT NULL DEFAULT '',
            updated_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS projects (
            id $id,
            title VARCHAR(180) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            summary VARCHAR(300) NOT NULL DEFAULT '',
            description TEXT NULL,
            category VARCHAR(80) NOT NULL DEFAULT 'Web App',
            client VARCHAR(150) NOT NULL DEFAULT '',
            role VARCHAR(150) NOT NULL DEFAULT '',
            year VARCHAR(10) NOT NULL DEFAULT '',
            tech_stack VARCHAR(300) NOT NULL DEFAULT '',
            case_challenge TEXT NULL,
            case_solution TEXT NULL,
            case_result TEXT NULL,
            case_metrics TEXT NULL,
            project_url VARCHAR(255) NOT NULL DEFAULT '',
            repo_url VARCHAR(255) NOT NULL DEFAULT '',
            cover VARCHAR(190) NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 0,
            views INT NOT NULL DEFAULT 0,
            created_at VARCHAR(25) NOT NULL,
            updated_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS project_images (
            id $id,
            project_id BIGINT NOT NULL,
            file_name VARCHAR(190) NOT NULL,
            caption VARCHAR(200) NOT NULL DEFAULT '',
            sort_order INT NOT NULL DEFAULT 0,
            created_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS project_releases (
            id $id,
            project_id BIGINT NOT NULL,
            version VARCHAR(40) NOT NULL,
            title VARCHAR(150) NOT NULL DEFAULT '',
            notes TEXT NULL,
            file_name VARCHAR(190) NULL,
            original_name VARCHAR(190) NULL,
            file_size BIGINT NOT NULL DEFAULT 0,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            downloads INT NOT NULL DEFAULT 0,
            released_at VARCHAR(25) NULL,
            created_at VARCHAR(25) NOT NULL,
            updated_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS skills (
            id $id,
            name VARCHAR(80) NOT NULL,
            category VARCHAR(60) NOT NULL DEFAULT 'Umum',
            level INT NOT NULL DEFAULT 80,
            sort_order INT NOT NULL DEFAULT 0
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS messages (
            id $id,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(200) NOT NULL DEFAULT '',
            body TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            created_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS settings (
            setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS activity_logs (
            id $id,
            user_id INT NULL,
            actor VARCHAR(190) NOT NULL DEFAULT '',
            action VARCHAR(60) NOT NULL,
            description VARCHAR(255) NOT NULL DEFAULT '',
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(255) NOT NULL DEFAULT '',
            created_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS posts (
            id $id,
            title VARCHAR(180) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            excerpt VARCHAR(300) NOT NULL DEFAULT '',
            content TEXT NULL,
            category VARCHAR(80) NOT NULL DEFAULT 'Artikel',
            tags VARCHAR(200) NOT NULL DEFAULT '',
            cover VARCHAR(190) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            reading_minutes INT NOT NULL DEFAULT 0,
            views INT NOT NULL DEFAULT 0,
            published_at VARCHAR(25) NULL,
            created_at VARCHAR(25) NOT NULL,
            updated_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS visits (
            id $id,
            path VARCHAR(190) NOT NULL,
            referrer VARCHAR(190) NOT NULL DEFAULT '',
            is_bot TINYINT(1) NOT NULL DEFAULT 0,
            created_at VARCHAR(25) NOT NULL
        )$suffix";

        $tables[] = "CREATE TABLE IF NOT EXISTS login_attempts (
            id $id,
            email VARCHAR(190) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempted_at VARCHAR(25) NOT NULL
        )$suffix";

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }

        // Kolom baru untuk instalasi lama (schema v2 → v3).
        self::addColumnIfMissing($pdo, 'users', 'totp_secret', 'VARCHAR(64) NULL');
        self::addColumnIfMissing($pdo, 'users', 'totp_enabled_at', 'VARCHAR(25) NULL');
        self::addColumnIfMissing($pdo, 'users', 'totp_recovery', 'TEXT NULL');

        // Kolom studi kasus project (schema v3 → v4).
        self::addColumnIfMissing($pdo, 'projects', 'case_challenge', 'TEXT NULL');
        self::addColumnIfMissing($pdo, 'projects', 'case_solution', 'TEXT NULL');
        self::addColumnIfMissing($pdo, 'projects', 'case_result', 'TEXT NULL');
        self::addColumnIfMissing($pdo, 'projects', 'case_metrics', 'TEXT NULL');

        if ($mysql) {
            // Index dibuat terpisah karena MySQL tidak punya IF NOT EXISTS untuk index.
            self::createIndexIfMissing($pdo, 'project_images', 'idx_project_images_project', 'project_id');
            self::createIndexIfMissing($pdo, 'project_releases', 'idx_project_releases_project', 'project_id');
            self::createIndexIfMissing($pdo, 'projects', 'idx_projects_status', 'status');
            self::createIndexIfMissing($pdo, 'messages', 'idx_messages_read', 'is_read');
            self::createIndexIfMissing($pdo, 'login_attempts', 'idx_login_attempts_email', 'email');
            self::createIndexIfMissing($pdo, 'activity_logs', 'idx_activity_logs_created', 'created_at');
            self::createIndexIfMissing($pdo, 'visits', 'idx_visits_created', 'created_at');
            self::createIndexIfMissing($pdo, 'posts', 'idx_posts_status', 'status');
        } else {
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_project_images_project ON project_images (project_id)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_project_releases_project ON project_releases (project_id)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_projects_status ON projects (status)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_messages_read ON messages (is_read)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts (email)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_activity_logs_created ON activity_logs (created_at)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_visits_created ON visits (created_at)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_posts_status ON posts (status)');
        }

        self::seed($pdo);

        if (!is_dir(dirname($marker)) && !@mkdir(dirname($marker), 0775, true) && !is_dir(dirname($marker))) {
            // diamkan saja; migrasi tetap berjalan setiap request bila folder tidak writable
        }
        @file_put_contents($marker, self::SCHEMA_VERSION);
    }

    /**
     * Koneksi mentah tanpa memicu migrasi (dipakai migrate()).
     */
    private static function rawConnection(): PDO
    {
        $driver = Config::string('db.connection', 'sqlite');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                Config::string('db.host', '127.0.0.1'),
                Config::int('db.port', 3306),
                Config::string('db.name', 'portfolio')
            );

            return new PDO($dsn, Config::string('db.username'), Config::string('db.password'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        $database = Config::string('db.database', 'storage/database.sqlite');
        $path = str_starts_with($database, '/') ? $database : base_path() . '/' . ltrim($database, '/');

        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Isi data awal: akun owner, profil, contoh project, skill, dan setting.
     */
    private static function seed(PDO $pdo): void
    {
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $now = date('Y-m-d H:i:s');

        if ($userCount === 0) {
            $email = (string) Config::env('OWNER_EMAIL', 'owner@example.com');
            $password = (string) Config::env('OWNER_PASSWORD', 'Owner12345!');
            $name = (string) Config::env('OWNER_NAME', 'Owner');

            $statement = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
                 VALUES (:name, :email, :hash, :role, 1, :created, :updated)'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'owner',
                'created' => $now,
                'updated' => $now,
            ]);
        }

        $profileCount = (int) $pdo->query('SELECT COUNT(*) FROM profiles')->fetchColumn();
        if ($profileCount === 0) {
            $statement = $pdo->prepare(
                'INSERT INTO profiles (full_name, headline, tagline, bio, location, public_email, availability,
                    years_experience, projects_completed, clients_count, github, linkedin, instagram, website, updated_at)
                 VALUES (:full_name, :headline, :tagline, :bio, :location, :email, :availability, 3, 24, 12, :github, :linkedin, :instagram, :website, :updated)'
            );
            $statement->execute([
                'full_name' => (string) Config::env('OWNER_NAME', 'Nama Anda'),
                'headline' => 'Full-Stack Developer & UI Engineer',
                'tagline' => 'Membangun aplikasi web yang cepat, rapi, dan nyaman dipakai — dari ide sampai produksi.',
                'bio' => "Halo! Saya seorang developer yang fokus pada aplikasi web modern. Saya senang mengubah masalah yang rumit menjadi antarmuka sederhana dan sistem yang andal.\n\nSaya bekerja dengan PHP, JavaScript, dan database relasional, serta terbiasa menangani deployment di server produksi termasuk panel Pterodactyl. Silakan jelajahi karya saya di bawah, atau hubungi saya untuk berkolaborasi.",
                'location' => 'Pekanbaru, Indonesia',
                'email' => (string) Config::env('OWNER_EMAIL', 'owner@example.com'),
                'availability' => 'Terbuka untuk proyek freelance',
                'github' => 'https://github.com/',
                'linkedin' => 'https://linkedin.com/',
                'instagram' => '',
                'website' => '',
                'updated' => $now,
            ]);
        }

        $projectCount = (int) $pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
        if ($projectCount === 0) {
            $demo = [
                [
                    'title' => 'Sistem Kasir Toko Online',
                    'summary' => 'Aplikasi point-of-sale dengan laporan penjualan realtime dan manajemen stok multi-gudang.',
                    'description' => "Aplikasi kasir berbasis web untuk toko retail dengan kebutuhan multi-gudang.\n\nFitur utama:\n- Transaksi cepat dengan dukungan scanner barcode\n- Manajemen stok otomatis antar gudang\n- Laporan penjualan harian, mingguan, dan bulanan\n- Cetak struk thermal 58mm\n\nTantangan terbesar adalah menjaga konsistensi stok saat beberapa kasir bertransaksi bersamaan, diselesaikan dengan transaksi database dan row-level locking.",
                    'category' => 'Web App',
                    'client' => 'Retail Lokal',
                    'role' => 'Full-Stack Developer',
                    'year' => '2025',
                    'tech_stack' => 'PHP 8.3, MySQL, Alpine.js, Tailwind CSS',
                    'is_featured' => 1,
                    'cover_color' => '#6366f1',
                    'cover_label' => 'POS',
                ],
                [
                    'title' => 'Dashboard Monitoring Server',
                    'summary' => 'Panel pemantauan CPU, RAM, dan disk untuk beberapa node Pterodactyl dalam satu layar.',
                    'description' => "Dashboard ringan untuk memantau kondisi beberapa server game panel dalam satu tampilan.\n\nFitur utama:\n- Grafik realtime penggunaan CPU, RAM, dan disk\n- Notifikasi Telegram saat resource melewati ambang batas\n- Riwayat pemakaian 30 hari terakhir\n- Autentikasi role admin dan viewer\n\nDibangun dengan polling API terjadwal dan penyimpanan metrik time-series sederhana agar tetap ringan di VPS 1GB RAM.",
                    'category' => 'Dashboard',
                    'client' => 'Komunitas Server',
                    'role' => 'Backend Developer',
                    'year' => '2025',
                    'tech_stack' => 'PHP 8.3, SQLite, Chart.js, Pterodactyl API',
                    'is_featured' => 1,
                    'cover_color' => '#0ea5e9',
                    'cover_label' => 'MON',
                ],
                [
                    'title' => 'Company Profile Studio Kreatif',
                    'summary' => 'Situs profil perusahaan dengan CMS kustom untuk mengelola portofolio dan artikel.',
                    'description' => "Situs profil untuk studio kreatif dengan kebutuhan editorial yang cukup sering berubah.\n\nSaya membangun CMS kustom yang ringan agar tim non-teknis bisa menambah studi kasus, mengatur galeri, dan memperbarui halaman layanan tanpa bantuan developer.\n\nHasilnya, waktu publikasi konten turun dari beberapa jam menjadi beberapa menit.",
                    'category' => 'Website',
                    'client' => 'Studio Kreatif',
                    'role' => 'Web Developer',
                    'year' => '2024',
                    'tech_stack' => 'PHP, MySQL, CSS Grid, Vanilla JS',
                    'is_featured' => 0,
                    'cover_color' => '#f59e0b',
                    'cover_label' => 'WEB',
                ],
            ];

            $insert = $pdo->prepare(
                'INSERT INTO projects (title, slug, summary, description, category, client, role, year, tech_stack,
                    project_url, repo_url, cover, is_featured, status, sort_order, created_at, updated_at)
                 VALUES (:title, :slug, :summary, :description, :category, :client, :role, :year, :tech,
                    :project_url, :repo_url, :cover, :featured, :status, :sort, :created, :updated)'
            );

            $imageInsert = $pdo->prepare(
                'INSERT INTO project_images (project_id, file_name, caption, sort_order, created_at)
                 VALUES (:project_id, :file_name, :caption, :sort_order, :created_at)'
            );

            $order = 1;
            foreach ($demo as $item) {
                $slug = self::uniqueSlug($pdo, $item['title']);
                $cover = self::demoCover($item['cover_label'], $item['cover_color'], $item['title']);

                $insert->execute([
                    'title' => $item['title'],
                    'slug' => $slug,
                    'summary' => $item['summary'],
                    'description' => $item['description'],
                    'category' => $item['category'],
                    'client' => $item['client'],
                    'role' => $item['role'],
                    'year' => $item['year'],
                    'tech' => $item['tech_stack'],
                    'project_url' => '',
                    'repo_url' => '',
                    'cover' => $cover,
                    'featured' => $item['is_featured'],
                    'status' => 'published',
                    'sort' => $order,
                    'created' => $now,
                    'updated' => $now,
                ]);

                $projectId = (int) $pdo->lastInsertId();
                $documents = [
                    ['UI', '#4f46e5', 'Tampilan utama'],
                    ['UX', '#0891b2', 'Alur penggunaan'],
                ];

                foreach ($documents as $index => [$label, $color, $caption]) {
                    $fileName = self::demoCover($label, $color, $item['title']);
                    if ($fileName === null) {
                        continue;
                    }

                    $imageInsert->execute([
                        'project_id' => $projectId,
                        'file_name' => $fileName,
                        'caption' => $caption . ' — ' . $item['title'],
                        'sort_order' => $index + 1,
                        'created_at' => $now,
                    ]);
                }

                self::seedRelease($pdo, $projectId, $item['title'], $item['year'], $now);

                $order++;
            }
        }

        $skillCount = (int) $pdo->query('SELECT COUNT(*) FROM skills')->fetchColumn();
        if ($skillCount === 0) {
            $skills = [
                ['PHP 8.3', 'Backend', 92],
                ['MySQL / MariaDB', 'Backend', 88],
                ['SQLite', 'Backend', 85],
                ['JavaScript (ES2023)', 'Frontend', 90],
                ['Tailwind CSS', 'Frontend', 86],
                ['Alpine.js', 'Frontend', 80],
                ['Linux & Server', 'Infrastruktur', 82],
                ['Git & CI/CD', 'Infrastruktur', 78],
                ['UI/UX Design', 'Desain', 74],
            ];
            $insert = $pdo->prepare(
                'INSERT INTO skills (name, category, level, sort_order) VALUES (:name, :category, :level, :sort)'
            );
            $i = 1;
            foreach ($skills as [$name, $category, $level]) {
                $insert->execute(['name' => $name, 'category' => $category, 'level' => $level, 'sort' => $i++]);
            }
        }

        $settings = [
            'site_title' => 'Portfolio',
            'meta_description' => 'Portofolio pribadi — karya, dokumentasi, dan cara menghubungi saya.',
            'accent_color' => (string) Config::get('accent', '#6d8bff'),
            'footer_text' => 'Dibuat dengan PHP 8.3 — berjalan ringan di Pterodactyl.',
            'show_contact' => '1',
            'allow_registration' => Config::bool('registration', true) ? '1' : '0',
        ];

        $isMysql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';

        $settingInsert = $pdo->prepare(
            $isMysql
                ? 'INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:key, :value)'
                : 'INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (:key, :value)'
        );

        foreach ($settings as $key => $value) {
            $settingInsert->execute(['key' => $key, 'value' => $value]);
        }
    }

    private static function uniqueSlug(PDO $pdo, string $title): string
    {
        $base = \slugify($title);
        $slug = $base;
        $i = 2;

        $statement = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE slug = :slug');
        while (true) {
            $statement->execute(['slug' => $slug]);
            if ((int) $statement->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    /**
     * Buat gambar cover demo (SVG) agar tampilan awal tidak kosong.
     */
    private static function demoCover(string $label, string $color, string $title): ?string
    {
        $directory = base_path('storage/uploads');
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        $name = 'demo-' . bin2hex(random_bytes(5)) . '.svg';
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$color}"/>
      <stop offset="100%" stop-color="#0b1020"/>
    </linearGradient>
    <pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse">
      <path d="M48 0H0v48" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="1"/>
    </pattern>
  </defs>
  <rect width="1200" height="800" fill="url(#g)"/>
  <rect width="1200" height="800" fill="url(#grid)"/>
  <circle cx="980" cy="170" r="240" fill="rgba(255,255,255,.10)"/>
  <circle cx="240" cy="680" r="180" fill="rgba(0,0,0,.18)"/>
  <text x="80" y="400" font-family="Helvetica, Arial, sans-serif" font-size="150" font-weight="700" fill="rgba(255,255,255,.92)">{$label}</text>
  <text x="84" y="470" font-family="Helvetica, Arial, sans-serif" font-size="34" fill="rgba(255,255,255,.75)">{$safeTitle}</text>
</svg>
SVG;

        if (@file_put_contents($directory . '/' . $name, $svg) === false) {
            return null;
        }

        return $name;
    }

    /**
     * Contoh rilis/unduhan untuk project demo.
     */
    private static function seedRelease(PDO $pdo, int $projectId, string $title, string $year, string $now): void
    {
        $directory = base_path('storage/releases');
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return;
        }

        $fileName = 'demo-release-' . bin2hex(random_bytes(5)) . '.txt';
        $content = "Rilis demo v1.0.0 — {$title}\n"
            . "Tahun: {$year}\n\n"
            . "Ini contoh file rilis. Ganti dengan file asli Anda (ZIP/PDF/APK) lewat\n"
            . "panel admin: Portofolio -> Edit project -> Releases & unduhan.\n";

        if (@file_put_contents($directory . '/' . $fileName, $content) === false) {
            return;
        }

        $statement = $pdo->prepare(
            'INSERT INTO project_releases (project_id, version, title, notes, file_name, original_name,
                file_size, is_published, downloads, released_at, created_at, updated_at)
             VALUES (:project_id, :version, :title, :notes, :file_name, :original_name,
                :file_size, 1, 0, :released_at, :created_at, :updated_at)'
        );

        $statement->execute([
            'project_id' => $projectId,
            'version' => '1.0.0',
            'title' => 'Rilis awal',
            'notes' => "Fitur utama versi pertama:\n- Modul inti aplikasi\n- Antarmuka dasar\n- Perbaikan bug awal",
            'file_name' => $fileName,
            'original_name' => \slugify($title) . '-v1.0.0.txt',
            'file_size' => strlen($content),
            'released_at' => date('Y-m-d', strtotime($year . '-01-15') ?: time()),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private static function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
    {
        try {
            if (self::driver() === 'mysql') {
                $statement = $pdo->prepare(
                    'SELECT 1 FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
                );
                $statement->execute(['table' => $table, 'column' => $column]);
                if ($statement->fetch()) {
                    return;
                }
            } else {
                $columns = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
                foreach ($columns as $info) {
                    if (($info['name'] ?? '') === $column) {
                        return;
                    }
                }
            }

            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
        } catch (PDOException) {
            // kolom sudah ada / tidak punya izin — bukan masalah fatal
        }
    }

    private static function createIndexIfMissing(PDO $pdo, string $table, string $index, string $column): void
    {
        try {
            $statement = $pdo->prepare('SHOW INDEX FROM ' . $table . ' WHERE Key_name = :name');
            $statement->execute(['name' => $index]);
            if ($statement->fetch()) {
                return;
            }
            $pdo->exec("CREATE INDEX $index ON $table ($column)");
        } catch (PDOException) {
            // index sudah ada / tidak punya izin — tidak fatal
        }
    }
}
