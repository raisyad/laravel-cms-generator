<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class Cms
{
    /**
     * Resolve CMS resources from config with safe route checks.
     *
     * Each item contains:
     * - key: string
     * - label: string
     * - icon: ?string
     * - routeName: string
     * - url: string
     * - active: bool
     *
     * Items with invisible flag or missing routes are filtered out.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolvedResources(): array
    {
        $configResources = config('cms.resources', []);
        $resolved = [];

        foreach ($configResources as $key => $resource) {
            $visible = Arr::get($resource, 'visible', true);
            if (! $visible) {
                continue;
            }

            $configuredRoute = Arr::get($resource, 'route');
            if (is_string($configuredRoute) && $configuredRoute !== '') {
                $routeName = $configuredRoute;
            } else {
                $routeName = $key === 'dashboard' ? 'cms.dashboard' : "cms.$key.index";
            }

            // Skip if route does not exist; never generate broken links.
            if (! Route::has($routeName)) {
                continue;
            }

            $label = Arr::get($resource, 'label', Str::headline($key));
            $icon = Arr::get($resource, 'icon');

            // Active state: true if current route matches the route name or resource pattern.
            $currentRoute = request()->route()?->getName() ?? '';
            $active = false;
            if ($currentRoute !== '') {
                $active = request()->routeIs($routeName)
                    || request()->routeIs("$routeName.*")
                    || request()->routeIs("cms.$key*");
            }

            $resolved[] = [
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'routeName' => $routeName,
                'url' => route($routeName),
                'active' => $active,
            ];
        }

        return $resolved;
    }
}
