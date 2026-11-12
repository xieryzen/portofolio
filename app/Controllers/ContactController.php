<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Message;
use App\Notifier;
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

        $subject = $data['subject'] !== '' ? $data['subject'] : 'Pesan dari situs portofolio';

        Message::create($data['name'], $data['email'], $subject, $data['body'], client_ip());

        // Notifikasi bersifat opsional — kegagalan kanal tidak boleh menggagalkan form.
        try {
            Notifier::notifyContact([
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $subject,
                'body' => $data['body'],
            ]);
        } catch (\Throwable) {
            // diamkan; detail tercatat di storage/logs/notify.log
        }

        $_SESSION['_contact_last'] = time();
        $this->clearFormState();
        \flash('success', 'Terima kasih, ' . $data['name'] . '! Pesan Anda sudah terkirim.');
        $this->redirect('/#contact');
    }
}
