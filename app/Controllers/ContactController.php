<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use App\Validator;

final class ContactController extends Controller
{
    public function store(): void
    {
        // Honeypot: bot biasanya mengisi field tersembunyi.
        if ($this->inputString('website') !== '') {
            \flash('success', 'Pesan Anda sudah terkirim. Terima kasih!');
            $this->redirect('/#contact');
        }

        $lastSent = (int) ($_SESSION['_contact_last'] ?? 0);
        if ($lastSent > 0 && (time() - $lastSent) < 45) {
            \flash('info', 'Pesan sebelumnya baru saja terkirim. Mohon tunggu sebentar.');
            $this->redirect('/#contact');
        }

        $data = [
            'name' => $this->inputString('name'),
            'email' => $this->inputString('email'),
            'subject' => $this->inputString('subject'),
            'body' => $this->cleanText($this->inputString('body'), 5000),
        ];

        $validator = new Validator(
            $data,
            [
                'name' => 'required|min:2|max:120',
                'email' => 'required|email|max:190',
                'subject' => 'max:200',
                'body' => 'required|min:10|max:5000',
            ],
            ['name' => 'Nama', 'email' => 'Email', 'subject' => 'Subjek', 'body' => 'Pesan']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/#contact');
        }

        Message::create(
            $data['name'],
            $data['email'],
            $data['subject'] !== '' ? $data['subject'] : 'Pesan dari situs portofolio',
            $data['body'],
            client_ip()
        );

        $_SESSION['_contact_last'] = time();
        $this->clearFormState();
        \flash('success', 'Terima kasih, ' . $data['name'] . '! Pesan Anda sudah terkirim.');
        $this->redirect('/#contact');
    }
}
