<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $pdo = \App\Database::connection();
        $recentProjects = $pdo->query('SELECT * FROM projects ORDER BY updated_at DESC, id DESC LIMIT 5')->fetchAll();
        $recentMessages = Message::all(4);

        $this->view('admin/dashboard', [
            'title' => 'Ringkasan',
            'profile' => Profile::get(),
            'projectStats' => Project::stats(),
            'userStats' => User::stats(),
            'unreadMessages' => Message::unreadCount(),
            'recentProjects' => $recentProjects,
            'recentMessages' => $recentMessages,
        ], 'layouts/admin');
    }
}
