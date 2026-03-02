<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Http\Controllers;

use Laragod\Toolkit\Attributes\Sitemap;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;

final class SitemapController extends Controller
{
    /**
     * Available locales for hreflang tags.
     *
     * @var array<int, string>
     */
    private array $locales;

    /**
     * Base URL for building absolute URLs.
     */
    private string $baseUrl;

    /**
     * Controllers to scan for Sitemap attributes.
     *
     * @var array<int, class-string>
     */
    private array $controllers;

    public function __construct()
    {
        $this->locales = array_keys(available_locales());
        $this->baseUrl = rtrim((string) config('sitemap.base_url', config('app.url', 'https://example.com')), '/');
        $controllers = config('sitemap.controllers', []);
        $this->controllers = is_array($controllers) ? $controllers : [];
    }

    public function index(): Response
    {
        $urls = $this->collectUrls();
        $xml = $this->generateXml($urls);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Collect all URLs from controllers with Sitemap attributes.
     *
     * @return array<int, array{loc: string, priority: float, changefreq: string, alternates: array<string, string>}>
     */
    private function collectUrls(): array
    {
        $urls = [];
        $routeMap = $this->buildRouteMap();

        foreach ($this->controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(Sitemap::class);

                if (empty($attributes)) {
                    continue;
                }

                /** @var Sitemap $sitemap */
                $sitemap = $attributes[0]->newInstance();

                if (!$sitemap->enabled) {
                    continue;
                }

                $methodName = $method->getName();
                $controllerKey = $this->getControllerKey($controllerClass);
                $routeKey = $controllerKey . '@' . $methodName;

                if (!isset($routeMap[$routeKey])) {
                    continue;
                }

                $routePattern = $routeMap[$routeKey];

                if ($sitemap->slugsMethod !== null) {
                    $slugs = $this->getSlugsFromMethod($controllerClass, $sitemap->slugsMethod);

                    foreach ($slugs as $slug) {
                        $entries = $this->buildUrlEntries(
                            $routePattern,
                            $sitemap,
                            [$sitemap->slugParam => $slug],
                        );
                        $urls = [...$urls, ...$entries];
                    }
                } else {
                    $entries = $this->buildUrlEntries($routePattern, $sitemap);
                    $urls = [...$urls, ...$entries];
                }
            }
        }

        usort($urls, fn (array $a, array $b) => $b['priority'] <=> $a['priority']);

        return $urls;
    }

    /**
     * Build a map of controller@method => route pattern.
     *
     * @return array<string, string>
     */
    private function buildRouteMap(): array
    {
        $map = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction();

            if (!isset($action['controller'])) {
                continue;
            }

            $controllerAction = $action['controller'];

            if (!str_contains($controllerAction, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $controllerAction);
            $controllerKey = $this->getControllerKey($controller);
            $key = $controllerKey . '@' . $method;

            $uri = $route->uri();

            if (str_starts_with($uri, '{locale}')) {
                $map[$key] = $uri;
            }
        }

        return $map;
    }

    /**
     * Get a simplified key for a controller class.
     */
    private function getControllerKey(string $controllerClass): string
    {
        $parts = explode('\\', $controllerClass);

        return end($parts);
    }

    /**
     * Get slugs from a static method on the controller.
     *
     * @return array<int, string>
     */
    private function getSlugsFromMethod(string $controllerClass, string $methodName): array
    {
        if (!method_exists($controllerClass, $methodName)) {
            return [];
        }

        return $controllerClass::$methodName();
    }

    /**
     * Build URL entries for all locales with alternates.
     *
     * @param array<string, string> $params
     * @return array<int, array{loc: string, priority: float, changefreq: string, alternates: array<string, string>}>
     */
    private function buildUrlEntries(string $routePattern, Sitemap $sitemap, array $params = []): array
    {
        $alternates = [];

        foreach ($this->locales as $locale) {
            $url = $this->buildUrl($routePattern, $locale, $params);
            $alternates[$locale] = $url;
        }

        $entries = [];

        foreach ($this->locales as $locale) {
            $entries[] = [
                'loc' => $alternates[$locale],
                'priority' => $sitemap->priority,
                'changefreq' => $sitemap->changefreq,
                'alternates' => $alternates,
            ];
        }

        return $entries;
    }

    /**
     * Build a full URL from a route pattern.
     *
     * @param array<string, string> $params
     */
    private function buildUrl(string $routePattern, string $locale, array $params = []): string
    {
        $path = str_replace('{locale}', $locale, $routePattern);

        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }

        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Generate XML sitemap from collected URLs.
     *
     * @param array<int, array{loc: string, priority: float, changefreq: string, alternates: array<string, string>}> $urls
     */
    private function generateXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($urls as $url) {
            $xml .= $this->generateUrlBlock($url);
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }

    /**
     * Generate a single URL block with hreflang alternates.
     *
     * @param array{loc: string, priority: float, changefreq: string, alternates: array<string, string>} $url
     */
    private function generateUrlBlock(array $url): string
    {
        $block = "    <url>\n";
        $block .= "        <loc>{$url['loc']}</loc>\n";

        foreach ($url['alternates'] as $locale => $href) {
            $block .= "        <xhtml:link rel=\"alternate\" hreflang=\"{$locale}\" href=\"{$href}\"/>\n";
        }

        $block .= "        <changefreq>{$url['changefreq']}</changefreq>\n";
        $block .= "        <priority>{$url['priority']}</priority>\n";
        $block .= "    </url>\n";

        return $block;
    }
}
