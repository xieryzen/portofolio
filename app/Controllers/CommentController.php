<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Validator;

/**
 * Pengiriman komentar publik (wajib moderasi admin).
 */
final class CommentController extends Controller
{
    public function store(string $slug): void
    {
        $post = Post::findBySlug($slug);

        if ($post === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Artikel tidak ditemukan']);

            return;
        }

        $target = '/blog/' . $post['slug'] . '#komentar';

        // Honeypot.
        if ($this->inputString('website') !== '') {
            \flash('success', 'Terima kasih! Komentar Anda menunggu moderasi.');
            $this->redirect($target);
        }

        $lastSent = (int) ($_SESSION['_comment_last'] ?? 0);
        if ($lastSent > 0 && (time() - $lastSent) < 30) {
            \flash('info', 'Komentar sebelumnya baru saja dikirim. Mohon tunggu sebentar.');
            $this->redirect($target);
        }

        $data = [
            'name' => $this->inputString('name'),
            'email' => $this->inputString('email'),
            'body' => $this->cleanText($this->inputString('body'), 2000),
        ];

        $validator = new Validator(
            $data,
            [
                'name' => 'required|min:2|max:120',
                'email' => 'email|max:190',
                'body' => 'required|min:10|max:2000',
            ],
            ['name' => 'Nama', 'email' => 'Email', 'body' => 'Komentar']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, $target);
        }

        if (preg_match_all('~https?://~i', $data['body']) > 2) {
            $this->backWithErrors(['body' => 'Komentar berisi terlalu banyak tautan.'], $data, $target);
        }

        Comment::create((int) $post['id'], $data['name'], $data['email'], $data['body']);

        $_SESSION['_comment_last'] = time();
        $this->clearFormState();
        \flash('success', 'Terima kasih, ' . $data['name'] . '! Komentar Anda menunggu moderasi.');
        $this->redirect($target);
    }
}
