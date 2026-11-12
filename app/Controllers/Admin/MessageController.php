<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Message;

final class MessageController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/messages', [
            'title' => 'Pesan Masuk',
            'messages' => Message::all(100),
            'unread' => Message::unreadCount(),
        ], 'layouts/admin');
    }

    public function toggleRead(string $id): void
    {
        Auth::requireAdmin();

        $message = Message::find((int) $id);
        if ($message) {
            Message::toggleRead((int) $message['id']);
        }

        $this->redirect('/admin/messages');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        $message = Message::find((int) $id);
        if ($message) {
            Message::delete((int) $message['id']);
            \flash('success', 'Pesan dihapus.');
        }

        $this->redirect('/admin/messages');
    }
}
