<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Comment;

final class CommentController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $status = $this->inputString('status');
        if (!in_array($status, ['pending', 'approved', 'all'], true)) {
            $status = 'pending';
        }

        $page = \query_int('page', 1);

        $this->view('admin/comments', [
            'title' => 'Komentar artikel',
            'result' => Comment::paginate($status, $page, 20),
            'status' => $status,
            'pending' => Comment::pendingCount(),
        ], 'layouts/admin');
    }

    public function approve(string $id): void
    {
        Auth::requireAdmin();

        Comment::approve((int) $id);
        ActivityLog::record('comment_approved', 'Menyetujui komentar #' . (int) $id);
        \flash('success', 'Komentar disetujui dan tampil di artikel.');
        $this->redirect('/admin/comments');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        Comment::delete((int) $id);
        ActivityLog::record('comment_deleted', 'Menghapus komentar #' . (int) $id);
        \flash('success', 'Komentar dihapus.');
        $this->redirect('/admin/comments');
    }
}
