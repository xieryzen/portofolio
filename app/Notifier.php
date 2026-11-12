<?php

declare(strict_types=1);

namespace App;

use Throwable;

/**
 * Notifikasi pesan kontak ke Telegram, webhook, dan/atau email (SMTP).
 *
 * Semua kanal bersifat opsional: bila tidak dikonfigurasi di .env,
 * kanal tersebut dilewati tanpa membuat form kontak gagal.
 */
final class Notifier
{
    private const LOG_FILE = 'notify.log';
    private const TIMEOUT = 6;

    /**
     * @return array{telegram: bool, webhook: bool, email: bool}
     */
    public static function channels(): array
    {
        return [
            'telegram' => self::telegramToken() !== '' && self::telegramChat() !== '',
            'webhook' => Config::env('NOTIFY_WEBHOOK_URL', '') !== '',
            'email' => self::emailTarget() !== '' && self::emailPossible(),
        ];
    }

    public static function enabled(): bool
    {
        return setting('notify_contact', '1') === '1';
    }

    public static function hasChannel(): bool
    {
        $channels = self::channels();

        return $channels['telegram'] || $channels['webhook'] || $channels['email'];
    }

    /**
     * Kirim notifikasi pesan kontak baru.
     *
     * @param array{name: string, email: string, subject: string, body: string} $message
     *
     * @return list<array{channel: string, ok: bool, detail: string}>
     */
    public static function notifyContact(array $message): array
    {
        if (!self::enabled() || !self::hasChannel()) {
            return [];
        }

        $siteName = (string) setting('site_title', 'Portfolio');
        $adminUrl = absolute_url('/admin/messages');

        $text = "📬 <b>Pesan baru dari {$siteName}</b>\n\n"
            . 'Nama: ' . self::escape($message['name']) . "\n"
            . 'Email: ' . self::escape($message['email']) . "\n"
            . 'Subjek: ' . self::escape($message['subject']) . "\n\n"
            . mb_substr($message['body'], 0, 700, 'UTF-8')
            . "\n\n" . self::escape($adminUrl);

        $payload = [
            'event' => 'contact.created',
            'site' => $siteName,
            'message' => [
                'name' => $message['name'],
                'email' => $message['email'],
                'subject' => $message['subject'],
                'body' => mb_substr($message['body'], 0, 1500, 'UTF-8'),
            ],
            'admin_url' => $adminUrl,
            'sent_at' => date('c'),
        ];

        return self::dispatch($text, $payload, $message);
    }

    /**
     * Kirim pesan percobaan ke semua kanal (dipakai tombol "Kirim tes").
     *
     * @return list<array{channel: string, ok: bool, detail: string}>
     */
    public static function sendTest(): array
    {
        if (!self::hasChannel()) {
            return [];
        }

        $siteName = (string) setting('site_title', 'Portfolio');
        $text = "🔔 <b>Tes notifikasi</b>\n\nPesan ini dikirim dari panel admin {$siteName} pada "
            . date('d M Y H:i') . '. Konfigurasi notifikasi berfungsi.';

        $payload = [
            'event' => 'contact.test',
            'site' => $siteName,
            'sent_at' => date('c'),
            'admin_url' => absolute_url('/admin'),
        ];

        return self::dispatch($text, $payload, [
            'name' => 'Panel Admin',
            'email' => (string) (Config::env('NOTIFY_EMAIL_TO') ?: Config::env('OWNER_EMAIL', '')),
            'subject' => 'Tes notifikasi',
            'body' => 'Ini pesan percobaan dari halaman Pengaturan.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{name: string, email: string, subject: string, body: string} $message
     *
     * @return list<array{channel: string, ok: bool, detail: string}>
     */
    private static function dispatch(string $text, array $payload, array $message): array
    {
        $results = [];

        if (self::channels()['telegram']) {
            $results[] = self::telegram($text);
        }

        if (self::channels()['webhook']) {
            $results[] = self::webhook($payload);
        }

        if (self::channels()['email']) {
            $results[] = self::email($message);
        }

        return $results;
    }

    /**
     * @return array{channel: string, ok: bool, detail: string}
     */
    private static function telegram(string $text): array
    {
        $url = 'https://api.telegram.org/bot' . self::telegramToken() . '/sendMessage';

        $response = self::httpPost($url, (string) json_encode([
            'chat_id' => self::telegramChat(),
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ], JSON_UNESCAPED_UNICODE), ['Content-Type: application/json']);

        if ($response['ok']) {
            $decoded = json_decode($response['body'], true);
            if (is_array($decoded) && ($decoded['ok'] ?? false) === true) {
                self::log('telegram', true, 'Pesan terkirim');

                return ['channel' => 'Telegram', 'ok' => true, 'detail' => 'Pesan terkirim'];
            }

            $detail = is_array($decoded) ? (string) ($decoded['description'] ?? 'Respons tidak dikenal') : 'Respons bukan JSON';
            self::log('telegram', false, $detail);

            return ['channel' => 'Telegram', 'ok' => false, 'detail' => $detail];
        }

        self::log('telegram', false, $response['detail']);

        return ['channel' => 'Telegram', 'ok' => false, 'detail' => $response['detail']];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{channel: string, ok: bool, detail: string}
     */
    private static function webhook(array $payload): array
    {
        $response = self::httpPost(
            (string) Config::env('NOTIFY_WEBHOOK_URL', ''),
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ['Content-Type: application/json']
        );

        $detail = $response['ok'] ? 'HTTP ' . $response['status'] : $response['detail'];
        self::log('webhook', $response['ok'], $detail);

        return ['channel' => 'Webhook', 'ok' => $response['ok'], 'detail' => $detail];
    }

    /**
     * @param array{name: string, email: string, subject: string, body: string} $message
     *
     * @return array{channel: string, ok: bool, detail: string}
     */
    private static function email(array $message): array
    {
        $to = self::emailTarget();
        $siteName = (string) setting('site_title', 'Portfolio');
        $subject = '[' . $siteName . '] ' . ($message['subject'] !== '' ? $message['subject'] : 'Pesan baru');

        $body = "Pesan baru dari formulir kontak {$siteName}\n\n"
            . 'Nama    : ' . $message['name'] . "\n"
            . 'Email   : ' . $message['email'] . "\n"
            . 'Subjek  : ' . $message['subject'] . "\n"
            . 'Waktu   : ' . date('d M Y H:i') . "\n\n"
            . "Isi pesan:\n" . $message['body'] . "\n\n"
            . 'Buka panel admin: ' . absolute_url('/admin/messages') . "\n";

        try {
            if ((string) Config::env('SMTP_HOST', '') === '') {
                $ok = function_exists('mail') && @mail($to, $subject, $body, self::mailHeaders($siteName));
                $detail = $ok ? 'Terkirim via mail()' : 'mail() tidak tersedia atau gagal';
                self::log('email', $ok, $detail);

                return ['channel' => 'Email', 'ok' => $ok, 'detail' => $detail];
            }

            self::smtpSend($to, $subject, $body, $siteName);
            self::log('email', true, 'Terkirim via SMTP');

            return ['channel' => 'Email', 'ok' => true, 'detail' => 'Terkirim via SMTP'];
        } catch (Throwable $exception) {
            $detail = $exception->getMessage();
            self::log('email', false, $detail);

            return ['channel' => 'Email', 'ok' => false, 'detail' => $detail];
        }
    }

    /**
     * @param list<string> $headers
     *
     * @return array{ok: bool, status: int, body: string, detail: string}
     */
    private static function httpPost(string $url, string $body, array $headers = []): array
    {
        if ($url === '') {
            return ['ok' => false, 'status' => 0, 'body' => '', 'detail' => 'URL kosong'];
        }

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'PortfolioCMS/1.0',
            ]);

            $response = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if ($response === false) {
                return ['ok' => false, 'status' => $status, 'body' => '', 'detail' => 'cURL: ' . $error];
            }

            return [
                'ok' => $status >= 200 && $status < 300,
                'status' => $status,
                'body' => (string) $response,
                'detail' => $status >= 200 && $status < 300 ? 'HTTP ' . $status : 'HTTP ' . $status,
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", array_merge($headers, ['User-Agent: PortfolioCMS/1.0'])),
                'content' => $body,
                'timeout' => self::TIMEOUT,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $statusLine = $http_response_header[0] ?? '';
        preg_match('~HTTP/\S+\s+(\d{3})~', $statusLine, $matches);
        $status = isset($matches[1]) ? (int) $matches[1] : 0;

        if ($response === false) {
            return ['ok' => false, 'status' => $status, 'body' => '', 'detail' => 'Koneksi gagal (allow_url_fopen?)'];
        }

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => (string) $response,
            'detail' => $status > 0 ? 'HTTP ' . $status : 'Respons tanpa status',
        ];
    }

    private static function smtpSend(string $to, string $subject, string $body, string $siteName): void
    {
        $host = (string) Config::env('SMTP_HOST', '');
        $port = (int) Config::env('SMTP_PORT', '587');
        $user = (string) Config::env('SMTP_USER', '');
        $pass = (string) Config::env('SMTP_PASS', '');
        $encryption = strtolower((string) Config::env('SMTP_ENCRYPTION', $port === 465 ? 'ssl' : 'tls'));
        $from = (string) (Config::env('MAIL_FROM', '') ?: ($user !== '' ? $user : 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));

        if (!function_exists('stream_socket_client')) {
            throw new \RuntimeException('stream_socket_client tidak tersedia di server ini');
        }

        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $socket = @stream_socket_client($remote, $errno, $errstr, self::TIMEOUT);

        if (!is_resource($socket)) {
            throw new \RuntimeException('SMTP tidak bisa terhubung: ' . ($errstr !== '' ? $errstr : 'error ' . $errno));
        }

        stream_set_timeout($socket, self::TIMEOUT);

        $expect = static function (array $codes) use ($socket): void {
            $response = '';

            while (($line = fgets($socket, 515)) !== false) {
                $response .= $line;
                if (preg_match('~^\d{3} ~', $line) === 1) {
                    break;
                }
            }

            $code = (int) substr($response, 0, 3);
            if (!in_array($code, $codes, true)) {
                throw new \RuntimeException('SMTP menolak: ' . trim($response));
            }
        };

        $send = static function (string $command) use ($socket): void {
            fwrite($socket, $command . "\r\n");
        };

        $expect([220]);
        $hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $send('EHLO ' . $hostname);
        $expect([250]);

        if ($encryption === 'tls') {
            $send('STARTTLS');
            $expect([220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('STARTTLS gagal');
            }
            $send('EHLO ' . $hostname);
            $expect([250]);
        }

        if ($user !== '') {
            $send('AUTH LOGIN');
            $expect([334]);
            $send(base64_encode($user));
            $expect([334]);
            $send(base64_encode($pass));
            $expect([235]);
        }

        $send('MAIL FROM:<' . $from . '>');
        $expect([250]);
        $send('RCPT TO:<' . $to . '>');
        $expect([250, 251]);
        $send('DATA');
        $expect([354]);

        $headers = self::mailHeaders($siteName);
        $message = 'From: ' . $siteName . ' <' . $from . ">\r\n"
            . 'To: <' . $to . ">\r\n"
            . 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n"
            . 'Date: ' . date('r') . "\r\n"
            . 'Message-ID: <' . bin2hex(random_bytes(8)) . '@' . $hostname . ">\r\n"
            . $headers . "\r\n"
            . str_replace("\n.", "\n..", str_replace(["\r\n", "\r"], "\n", $body));

        $send($message . "\r\n.");
        $expect([250]);
        $send('QUIT');
        fclose($socket);
    }

    private static function mailHeaders(string $siteName): string
    {
        return 'MIME-Version: 1.0' . "\r\n"
            . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
            . 'Content-Transfer-Encoding: 8bit' . "\r\n"
            . 'X-Mailer: Portfolio CMS (' . $siteName . ')';
    }

    private static function telegramToken(): string
    {
        return trim((string) Config::env('TELEGRAM_BOT_TOKEN', ''));
    }

    private static function telegramChat(): string
    {
        return trim((string) Config::env('TELEGRAM_CHAT_ID', ''));
    }

    private static function emailTarget(): string
    {
        return trim((string) (Config::env('NOTIFY_EMAIL_TO', '') ?: Config::env('OWNER_EMAIL', '')));
    }

    private static function emailPossible(): bool
    {
        if ((string) Config::env('SMTP_HOST', '') !== '') {
            return true;
        }

        return function_exists('mail');
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function log(string $channel, bool $ok, string $detail): void
    {
        $directory = base_path('storage/logs');
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $line = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'channel' => $channel,
            'ok' => $ok,
            'detail' => mb_substr($detail, 0, 300, 'UTF-8'),
        ], JSON_UNESCAPED_UNICODE);

        if ($line !== false) {
            @file_put_contents($directory . '/' . self::LOG_FILE, $line . "\n", FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * @return list<array{time: string, channel: string, ok: bool, detail: string}>
     */
    public static function recentLogs(int $limit = 12): array
    {
        $file = base_path('storage/logs/' . self::LOG_FILE);

        if (!is_file($file)) {
            return [];
        }

        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_slice($lines, -$limit);
        $items = [];

        foreach (array_reverse($lines) as $line) {
            $decoded = json_decode($line, true);
            if (!is_array($decoded)) {
                continue;
            }

            $items[] = [
                'time' => (string) ($decoded['time'] ?? ''),
                'channel' => (string) ($decoded['channel'] ?? ''),
                'ok' => (bool) ($decoded['ok'] ?? false),
                'detail' => (string) ($decoded['detail'] ?? ''),
            ];
        }

        return $items;
    }
}
