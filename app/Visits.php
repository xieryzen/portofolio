<?php

declare(strict_types=1);

namespace App;

/**
 * Statistik kunjungan ringan: hanya menyimpan path, host referrer, dan status bot.
 * Tidak ada IP, cookie, atau data pribadi.
 */
final class Visits
{
    private const BOT_PATTERN = '~bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|whatsapp|telegram|headless|lighthouse|curl|wget|python-requests|go-http-client|uptime|monitor|preview~i';

    private const IGNORED_PREFIXES = ['/admin', '/media', '/download', '/cv', '/two-factor', '/login', '/register', '/assets', '/icons'];

    public static function record(string $path): void
    {
        try {
            $path = parse_url($path, PHP_URL_PATH) ?: '/';
            $path = '/' . ltrim((string) $path, '/');

            if ($path === '/index.php') {
                $path = '/';
            }

            foreach (self::IGNORED_PREFIXES as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    return;
                }
            }

            $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $isBot = $userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;

            $statement = Database::connection()->prepare(
                'INSERT INTO visits (path, referrer, is_bot, created_at) VALUES (:path, :referrer, :is_bot, :created_at)'
            );
            $statement->execute([
                'path' => mb_substr($path, 0, 190, 'UTF-8'),
                'referrer' => self::referrerHost(),
                'is_bot' => $isBot ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (random_int(1, 100) === 1) {
                $cutoff = date('Y-m-d H:i:s', time() - 60 * 60 * 24 * 365);
                Database::connection()
                    ->prepare('DELETE FROM visits WHERE created_at < :cutoff')
                    ->execute(['cutoff' => $cutoff]);
            }
        } catch (\Throwable) {
            // Statistik tidak boleh menggagalkan halaman publik.
        }
    }

    /**
     * @return array{
     *   daily: list<array{date: string, total: int}>,
     *   totals: array{today: int, week: int, month: int, all: int},
     *   pages: list<array{path: string, total: int}>,
     *   referrers: list<array{referrer: string, total: int}>
     * }
     */
    public static function stats(int $days = 14): array
    {
        $pdo = Database::connection();
        $days = max(7, min(60, $days));

        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        $statement = $pdo->prepare(
            "SELECT substr(created_at, 1, 10) AS day, COUNT(*) AS total
             FROM visits
             WHERE is_bot = 0 AND substr(created_at, 1, 10) >= :start
             GROUP BY day ORDER BY day ASC"
        );
        $statement->execute(['start' => $start]);
        $byDay = [];
        foreach ($statement->fetchAll() as $row) {
            $byDay[(string) $row['day']] = (int) $row['total'];
        }

        $daily = [];
        for ($index = 0; $index < $days; $index++) {
            $date = date('Y-m-d', strtotime('-' . ($days - 1 - $index) . ' days'));
            $daily[] = ['date' => $date, 'total' => $byDay[$date] ?? 0];
        }

        $count = static function (string $sql, array $params = []) use ($pdo): int {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        };

        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-6 days'));
        $monthStart = date('Y-m-d', strtotime('-29 days'));

        $pages = $pdo->prepare(
            "SELECT path, COUNT(*) AS total FROM visits
             WHERE is_bot = 0 AND substr(created_at, 1, 10) >= :start
             GROUP BY path ORDER BY total DESC LIMIT 6"
        );
        $pages->execute(['start' => $monthStart]);

        $referrers = $pdo->prepare(
            "SELECT referrer, COUNT(*) AS total FROM visits
             WHERE is_bot = 0 AND referrer <> '' AND substr(created_at, 1, 10) >= :start
             GROUP BY referrer ORDER BY total DESC LIMIT 6"
        );
        $referrers->execute(['start' => $monthStart]);

        return [
            'daily' => $daily,
            'totals' => [
                'today' => $count('SELECT COUNT(*) FROM visits WHERE is_bot = 0 AND substr(created_at, 1, 10) = :day', ['day' => $today]),
                'week' => $count('SELECT COUNT(*) FROM visits WHERE is_bot = 0 AND substr(created_at, 1, 10) >= :day', ['day' => $weekStart]),
                'month' => $count('SELECT COUNT(*) FROM visits WHERE is_bot = 0 AND substr(created_at, 1, 10) >= :day', ['day' => $monthStart]),
                'all' => $count('SELECT COUNT(*) FROM visits WHERE is_bot = 0'),
            ],
            'pages' => $pages->fetchAll(),
            'referrers' => $referrers->fetchAll(),
        ];
    }

    private static function referrerHost(): string
    {
        $referrer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));

        if ($referrer === '') {
            return '';
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $host = preg_replace('~^www\.~', '', $host) ?? $host;

        return mb_substr($host, 0, 190, 'UTF-8');
    }
}
