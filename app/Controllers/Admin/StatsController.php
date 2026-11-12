<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Visits;

final class StatsController extends Controller
{
    public function index(): void
    {
        Auth::requireOwner();

        $this->view('admin/stats', [
            'title' => 'Statistik kunjungan',
            'stats' => Visits::stats(14),
        ], 'layouts/admin');
    }
}
