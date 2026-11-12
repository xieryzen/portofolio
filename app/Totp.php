<?php

declare(strict_types=1);

namespace App;

/**
 * TOTP (RFC 6238) tanpa dependensi — cocok untuk Google Authenticator,
 * Authy, 1Password, Bitwarden, dsb.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function code(string $secret, ?int $slice = null): string
    {
        $slice ??= intdiv(time(), self::PERIOD);
        $key = self::base32Decode($secret);

        if ($key === '') {
            return '';
        }

        $binary = pack('N*', 0) . pack('N*', $slice);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        $modulo = 10 ** self::DIGITS;

        return str_pad((string) ($value % $modulo), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('~\D~', '', $code) ?? '';

        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $slice = intdiv(time(), self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $expected = self::code($secret, $slice + $offset);
            if ($expected !== '' && hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    /**
     * @return list<string>
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $raw = strtoupper(bin2hex(random_bytes(5)));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
        }

        return $codes;
    }

    /**
     * @param list<string> $codes
     */
    public static function hashRecoveryCodes(array $codes): string
    {
        $hashes = array_map(
            static fn (string $code): string => password_hash(self::normalizeRecovery($code), PASSWORD_DEFAULT),
            $codes
        );

        return (string) json_encode(array_values($hashes));
    }

    /**
     * Kembalikan daftar hash baru bila kode cocok (kode terpakai dihapus).
     */
    public static function consumeRecoveryCode(?string $stored, string $input): ?string
    {
        $hashes = json_decode((string) $stored, true);

        if (!is_array($hashes) || $hashes === []) {
            return null;
        }

        $normalized = self::normalizeRecovery($input);

        if ($normalized === '') {
            return null;
        }

        foreach ($hashes as $index => $hash) {
            if (is_string($hash) && password_verify($normalized, $hash)) {
                unset($hashes[$index]);

                return (string) json_encode(array_values($hashes));
            }
        }

        return null;
    }

    public static function remainingRecoveryCodes(?string $stored): int
    {
        $hashes = json_decode((string) $stored, true);

        return is_array($hashes) ? count($hashes) : 0;
    }

    public static function normalizeRecovery(string $code): string
    {
        return strtoupper(preg_replace('~[^A-Za-z0-9]~', '', $code) ?? '');
    }

    public static function base32Encode(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $encoded .= self::ALPHABET[bindec($chunk)];
        }

        return $encoded;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('~[^A-Za-z2-7]~', '', $secret) ?? '');

        if ($secret === '') {
            return '';
        }

        $bits = '';
        foreach (str_split($secret) as $char) {
            $position = strpos(self::ALPHABET, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $binary = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr(bindec($byte));
            }
        }

        return $binary;
    }
}
