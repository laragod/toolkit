<?php

declare(strict_types=1);

if (!function_exists('locale_route')) {
    /**
     * Generate a URL for a named route with the current locale.
     *
     * @param array<string, mixed> $parameters
     */
    function locale_route(string $name, array $parameters = [], bool $absolute = true): string
    {
        $locale = app()->getLocale();

        return route($name, array_merge(['locale' => $locale], $parameters), $absolute);
    }
}

if (!function_exists('route_with_locale')) {
    /**
     * Generate a URL for a named route with a specific locale.
     *
     * @param array<string, mixed> $parameters
     */
    function route_with_locale(string $name, string $locale, array $parameters = [], bool $absolute = true): string
    {
        return route($name, array_merge(['locale' => $locale], $parameters), $absolute);
    }
}

if (!function_exists('available_locales')) {
    /**
     * Get all available locales.
     *
     * @return array<string, string>
     */
    function available_locales(): array
    {
        $locales = config('localization.locales');

        if (!is_array($locales)) {
            return ['en' => 'English'];
        }

        $result = [];
        foreach ($locales as $key => $value) {
            if (!(is_string($key) && is_string($value))) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result !== [] ? $result : ['en' => 'English'];
    }
}

if (!function_exists('current_locale')) {
    /**
     * Get the current locale.
     */
    function current_locale(): string
    {
        return app()->getLocale();
    }
}
