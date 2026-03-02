<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Sitemap
{
    /**
     * @param float $priority Priority of this URL relative to other URLs (0.0 to 1.0)
     * @param string $changefreq How frequently the page is likely to change
     * @param bool $enabled Whether to include this route in the sitemap
     * @param string|null $slugsMethod Method name on the controller that returns slugs for dynamic routes
     * @param string $slugParam The route parameter name for dynamic slugs (default: 'slug')
     */
    public function __construct(
        public float $priority = 0.5,
        public string $changefreq = 'weekly',
        public bool $enabled = true,
        public ?string $slugsMethod = null,
        public string $slugParam = 'slug',
    ) {}
}
