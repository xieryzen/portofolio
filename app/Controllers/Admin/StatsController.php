<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Post;
use App\Models\Project;
use App\Visits;

final class StatsController extends Controller
{
    public function index(): void
    {
        Auth::requireOwner();

        $days = (int) ($_GET['hari'] ?? 14);
        if (!in_array($days, [7, 14, 30, 90], true)) {
            $days = 14;
        }

        $this->view('admin/stats', [
            'title' => 'Statistik kunjungan',
            'stats' => Visits::stats($days),
            'days' => $days,
            'popularProjects' => Project::mostViewed(5),
            'popularPosts' => Post::mostViewed(5),
        ], 'layouts/admin');
    }

    /**
     * Ekspor CSV: kunjungan harian (90 hari), halaman populer, dan referrer.
     */
    public function export(): void
    {
        Auth::requireOwner();

        $stats = Visits::stats(90);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="statistik-' . date('Ymd') . '.csv"');
        header('X-Content-Type-Options: nosniff');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fputcsv($output, ['tipe', 'label', 'nilai']);

        foreach ($stats['daily'] as $day) {
            fputcsv($output, ['tanggal', $day['date'], $day['total']]);
        }

        foreach ($stats['pages'] as $page) {
            fputcsv($output, ['halaman', $page['path'], $page['total']]);
        }

        foreach ($stats['referrers'] as $referrer) {
            fputcsv($output, ['referrer', $referrer['referrer'], $referrer['total']]);
        }

        fputcsv($output, ['ringkasan', 'hari_ini', $stats['totals']['today']]);
        fputcsv($output, ['ringkasan', '7_hari', $stats['totals']['week']]);
        fputcsv($output, ['ringkasan', '30_hari', $stats['totals']['month']]);
        fputcsv($output, ['ringkasan', 'total', $stats['totals']['all']]);
        fputcsv($output, ['ringkasan', 'bot_difilter', $stats['totals']['bots']]);

        fclose($output);
        exit;
    }
}
