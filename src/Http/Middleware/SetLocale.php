<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Sets the application locale based on:
     * 1. URL prefix (e.g., /en/about, /pl/contact)
     * 2. Cookie preference (for non-localized routes like POST /contact)
     * 3. Default locale as fallback
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locales = $this->getAvailableLocales();
        $defaultLocale = $this->getDefaultLocale();

        // Get locale from route parameter
        $locale = $request->route('locale');

        // If locale is in URL and valid, use it
        if (is_string($locale) && in_array($locale, $locales, true)) {
            App::setLocale($locale);

            // Queue cookie to remember preference
            Cookie::queue(
                $this->getCookieName(),
                $locale,
                $this->getCookieLifetime(),
            );

            return $next($request);
        }

        // For non-localized routes (like POST /contact), check cookie
        $cookieLocale = $request->cookie($this->getCookieName());

        if (is_string($cookieLocale) && in_array($cookieLocale, $locales, true)) {
            App::setLocale($cookieLocale);
        } else {
            App::setLocale($defaultLocale);
        }

        return $next($request);
    }

    private function getDefaultLocale(): string
    {
        $locale = config('localization.default');

        return is_string($locale) ? $locale : 'en';
    }

    private function getCookieName(): string
    {
        $name = config('localization.cookie_name');

        return is_string($name) ? $name : 'locale';
    }

    private function getCookieLifetime(): int
    {
        $lifetime = config('localization.cookie_lifetime');

        return is_int($lifetime) ? $lifetime : 43200;
    }

    /**
     * @return list<string>
     */
    private function getAvailableLocales(): array
    {
        $locales = config('localization.locales');

        return is_array($locales) ? array_keys($locales) : ['en'];
    }
}
