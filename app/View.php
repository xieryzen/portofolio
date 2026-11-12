<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Render template PHP dengan layout.
 */
final class View
{
    /** @var array<string, mixed> */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $viewFile = base_path('views/' . $view . '.php');
        if (!is_file($viewFile)) {
            throw new RuntimeException('View tidak ditemukan: ' . $view);
        }

        $data = array_merge(self::$shared, $data);

        $content = self::capture($viewFile, $data);

        if ($layout === null) {
            echo $content;

            return;
        }

        $layoutFile = base_path('views/' . $layout . '.php');
        if (!is_file($layoutFile)) {
            echo $content;

            return;
        }

        $data['content'] = $content;
        echo self::capture($layoutFile, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return (string) ob_get_clean();
    }

    /**
     * Potongan HTML untuk komponen (partial).
     *
     * @param array<string, mixed> $data
     */
    public static function partial(string $view, array $data = []): string
    {
        $file = base_path('views/' . $view . '.php');
        if (!is_file($file)) {
            return '';
        }

        return self::capture($file, array_merge(self::$shared, $data));
    }
}
