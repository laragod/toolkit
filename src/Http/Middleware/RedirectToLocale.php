<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RedirectToLocale
{
    public function handle(Request $request): Response
    {
        $defaultLocale = $this->getDefaultLocale();
        $locales = $this->getAvailableLocales();
        $cookieLocale = $request->cookie($this->getCookieName());

        $locale = is_string($cookieLocale) && in_array($cookieLocale, $locales, true) ? $cookieLocale : $defaultLocale;

        return to_route('home', ['locale' => $locale]);
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

    /**
     * @return list<string>
     */
    private function getAvailableLocales(): array
    {
        $locales = config('localization.locales');

        return is_array($locales) ? array_keys($locales) : ['en'];
    }
}
