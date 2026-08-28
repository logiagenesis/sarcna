<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP template engine: a view renders into a layout via sections.
 * No compilation step, nothing to install on the host.
 */
final class View
{
    private static array $shared = [];
    private static array $sections = [];
    private static array $stack = [];
    private static ?string $layout = null;

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(): array
    {
        return self::$shared;
    }

    public static function render(string $template, array $data = []): string
    {
        self::$layout   = null;
        self::$sections = [];

        $content = self::capture($template, $data);

        if (self::$layout === null) {
            return $content;
        }

        self::$sections['content'] = self::$sections['content'] ?? $content;

        $layout       = self::$layout;
        self::$layout = null;

        return self::capture($layout, $data);
    }

    /** Render a template without a layout — used for emails and partials. */
    public static function partial(string $template, array $data = []): string
    {
        return self::capture($template, $data);
    }

    private static function capture(string $template, array $data): string
    {
        $path = Config::get('paths.views') . '/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        extract(array_merge(self::$shared, $data), EXTR_SKIP);

        ob_start();

        try {
            require $path;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    public static function layout(string $layout): void
    {
        self::$layout = $layout;
    }

    public static function start(string $section): void
    {
        self::$stack[] = $section;
        ob_start();
    }

    public static function stop(): void
    {
        $section = array_pop(self::$stack);

        if ($section === null) {
            ob_end_clean();

            return;
        }

        self::$sections[$section] = (string) ob_get_clean();
    }

    public static function section(string $section, string $default = ''): string
    {
        return self::$sections[$section] ?? $default;
    }

    public static function hasSection(string $section): bool
    {
        return isset(self::$sections[$section]) && trim(self::$sections[$section]) !== '';
    }

    public static function include(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }
}
