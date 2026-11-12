<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\ActivityLog;

final class ActivityController extends Controller
{
    public function index(): void
    {
        Auth::requireOwner();

        $page = max(1, (int) ($_GET['halaman'] ?? 1));
        $result = ActivityLog::paginate($page, 40);

        $this->view('admin/activity', [
            'title' => 'Log aktivitas',
            'result' => $result,
        ], 'layouts/admin');
    }
}
