<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Concerns;

trait ResolvesAppName
{
    /**
     * Resolve the app signature for outbound notifications.
     *
     * Falls back from the package config to the host application's
     * app.name so consuming apps are identified without extra setup.
     */
    private function getAppName(): string
    {
        $appName = config('notifications.app_name');

        if (is_string($appName) && $appName !== '') {
            return $appName;
        }

        $fallback = config('app.name');

        return is_string($fallback) && $fallback !== '' ? $fallback : 'App';
    }
}
