<?php

declare(strict_types=1);

namespace App;

/**
 * Validator ringan dengan pesan berbahasa Indonesia.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules contoh: ['name' => 'required|min:3', 'email' => 'required|email']
     * @param array<string, string> $labels
     */
    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data = $data;

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $label = $labels[$field] ?? self::humanize($field);

            foreach (explode('|', $fieldRules) as $rule) {
                if ($rule === '') {
                    continue;
                }

                $parameters = [];
                if (str_contains($rule, ':')) {
                    [$rule, $raw] = explode(':', $rule, 2);
                    $parameters = explode(',', $raw);
                }

                $this->apply($field, $label, $rule, $value, $parameters);

                if (isset($this->errors[$field])) {
                    break;
                }
            }
        }
    }

    /**
     * @param list<string> $parameters
     */
    private function apply(string $field, string $label, string $rule, mixed $value, array $parameters): void
    {
        $string = is_string($value) ? trim($value) : (is_scalar($value) ? (string) $value : '');

        switch ($rule) {
            case 'required':
                if ($string === '' || $value === null) {
                    $this->errors[$field] = $label . ' wajib diisi.';
                }
                break;

            case 'email':
                if ($string !== '' && !filter_var($string, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Format email tidak valid.';
                }
                break;

            case 'url':
                if ($string !== '' && !filter_var($string, FILTER_VALIDATE_URL)) {
                    $this->errors[$field] = $label . ' harus berupa URL yang valid.';
                }
                break;

            case 'numeric':
                if ($string !== '' && !is_numeric($string)) {
                    $this->errors[$field] = $label . ' harus berupa angka.';
                }
                break;

            case 'min':
                $min = (int) ($parameters[0] ?? 0);
                if ($string !== '' && mb_strlen($string, 'UTF-8') < $min) {
                    $this->errors[$field] = $label . ' minimal ' . $min . ' karakter.';
                }
                break;

            case 'max':
                $max = (int) ($parameters[0] ?? 255);
                if (mb_strlen($string, 'UTF-8') > $max) {
                    $this->errors[$field] = $label . ' maksimal ' . $max . ' karakter.';
                }
                break;

            case 'in':
                if ($string !== '' && !in_array($string, $parameters, true)) {
                    $this->errors[$field] = $label . ' tidak valid.';
                }
                break;

            case 'confirmed':
                $other = $this->data[$field . '_confirmation'] ?? null;
                if ($string !== (string) $other) {
                    $this->errors[$field] = 'Konfirmasi ' . mb_strtolower($label) . ' tidak cocok.';
                }
                break;

            case 'unique':
                $table = $parameters[0] ?? '';
                $column = $parameters[1] ?? $field;
                $except = (int) ($parameters[2] ?? 0);
                if ($string !== '' && $table !== '' && $this->exists($table, $column, $string, $except)) {
                    $this->errors[$field] = $label . ' sudah digunakan.';
                }
                break;

            case 'alpha_dash':
                if ($string !== '' && !preg_match("~^[\\pL\\pN\\-_ .']+$~u", $string)) {
                    $this->errors[$field] = $label . ' hanya boleh berisi huruf, angka, spasi, dan tanda baca umum.';
                }
                break;
        }
    }

    private function exists(string $table, string $column, string $value, int $except): bool
    {
        $allowed = ['users'];
        if (!in_array($table, $allowed, true)) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM $table WHERE $column = :value";
        $params = ['value' => mb_strtolower($value)];

        if ($except > 0) {
            $sql .= ' AND id <> :id';
            $params['id'] = $except;
        }

        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : (string) reset($this->errors);
    }

    private static function humanize(string $field): string
    {
        $map = [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Kata sandi',
            'title' => 'Judul',
            'summary' => 'Ringkasan',
            'full_name' => 'Nama lengkap',
            'headline' => 'Headline',
            'tagline' => 'Tagline',
            'bio' => 'Deskripsi',
            'site_title' => 'Judul situs',
            'subject' => 'Subjek',
            'body' => 'Pesan',
            'skill' => 'Nama keahlian',
        ];

        return $map[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }
}
