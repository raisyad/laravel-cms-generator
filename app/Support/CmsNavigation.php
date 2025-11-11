<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CmsNavigation
{
    /**
     * Resolve the navigation items that should be displayed in the CMS layout.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function items(?Request $request = null): array
    {
        $resources = config('cms.resources', []);
        $request ??= request();

        $items = [];

        foreach ($resources as $key => $resource) {
            if (! is_array($resource)) {
                continue;
            }

            $visible = $resource['visible'] ?? true;

            if (! $visible) {
                continue;
            }

            $routeName = self::resolveRouteName($key, $resource);

            if (! $routeName || ! Route::has($routeName)) {
                continue;
            }

            $label = $resource['label'] ?? Str::headline($key);
            $icon = $resource['icon'] ?? null;

            $patterns = self::resolveActivePatterns($key, $routeName, $resource);

            $active = false;

            if ($request) {
                foreach ($patterns as $pattern) {
                    if ($request->routeIs($pattern)) {
                        $active = true;
                        break;
                    }
                }
            }

            $items[] = [
                'key' => $key,
                'label' => $label,
                'icon' => $icon,
                'route_name' => $routeName,
                'url' => route($routeName),
                'active' => $active,
            ];
        }

        return $items;
    }

    /**
     * Resolve the intended route name for a resource.
     *
     * @param  array<string, mixed>  $resource
     */
    protected static function resolveRouteName(string $key, array $resource): ?string
    {
        $explicit = $resource['route'] ?? null;

        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        if ($key === 'dashboard') {
            return 'cms.dashboard';
        }

        return "cms.{$key}.index";
    }

    /**
     * Resolve the patterns that should match an "active" navigation state.
     *
     * @param  array<string, mixed>  $resource
     * @return array<int, string>
     */
    protected static function resolveActivePatterns(string $key, string $routeName, array $resource): array
    {
        $patterns = [];

        if (! empty($resource['active_patterns']) && is_array($resource['active_patterns'])) {
            $patterns = array_filter($resource['active_patterns'], fn ($pattern) => is_string($pattern) && $pattern !== '');
        }

        $patterns[] = $routeName;
        $patterns[] = $routeName.'.*';
        $patterns[] = "cms.{$key}*";
        $patterns[] = "cms.{$key}.*";

        return array_values(array_unique($patterns));
    }

    /**
     * Get the label of the currently active CMS section/resource.
     *
     * Uses the same logic as items() to determine which resource is active,
     * then returns its label. Falls back to "Dashboard" for the main CMS route,
     * or "CMS" if no resource matches.
     */
    public static function currentSectionLabel(?Request $request = null): string
    {
        $resources = config('cms.resources', []);
        $request ??= request();

        // Check each resource to find the active one
        foreach ($resources as $key => $resource) {
            if (! is_array($resource)) {
                continue;
            }

            $visible = $resource['visible'] ?? true;
            if (! $visible) {
                continue;
            }

            $routeName = self::resolveRouteName($key, $resource);

            // Skip if route doesn't exist
            if (! $routeName || ! Route::has($routeName)) {
                continue;
            }

            $patterns = self::resolveActivePatterns($key, $routeName, $resource);

            // Check if current route matches any pattern for this resource
            if ($request) {
                foreach ($patterns as $pattern) {
                    if ($request->routeIs($pattern)) {
                        // Found active resource, return its label
                        return $resource['label'] ?? Str::headline($key);
                    }
                }
            }
        }

        // Fallback: if on main CMS route, return "Dashboard"
        if ($request && $request->routeIs('cms.dashboard')) {
            return 'Dashboard';
        }

        // Final fallback
        return 'CMS';
    }

    /**
     * Get the URL of the currently active CMS section/resource.
     *
     * Returns the index route URL for the active resource, or dashboard URL as fallback.
     */
    public static function currentSectionUrl(?Request $request = null): ?string
    {
        $resources = config('cms.resources', []);
        $request ??= request();

        // Check each resource to find the active one
        foreach ($resources as $key => $resource) {
            if (! is_array($resource)) {
                continue;
            }

            $visible = $resource['visible'] ?? true;
            if (! $visible) {
                continue;
            }

            $routeName = self::resolveRouteName($key, $resource);

            // Skip if route doesn't exist
            if (! $routeName || ! Route::has($routeName)) {
                continue;
            }

            $patterns = self::resolveActivePatterns($key, $routeName, $resource);

            // Check if current route matches any pattern for this resource
            if ($request) {
                foreach ($patterns as $pattern) {
                    if ($request->routeIs($pattern)) {
                        // Found active resource, return its URL
                        return route($routeName);
                    }
                }
            }
        }

        // Fallback: if on main CMS route or no match, return dashboard URL
        if (Route::has('cms.dashboard')) {
            return route('cms.dashboard');
        }

        return null;
    }

    /**
     * Get the key of the currently active CMS section/resource.
     *
     * Uses the same logic as items() to determine which resource is active,
     * then returns its key. Falls back to "dashboard" for the main CMS route,
     * or null if no resource matches.
     */
    public static function currentSectionKey(?Request $request = null): ?string
    {
        $resources = config('cms.resources', []);
        $request ??= request();

        // Check each resource to find the active one
        foreach ($resources as $key => $resource) {
            if (! is_array($resource)) {
                continue;
            }

            $visible = $resource['visible'] ?? true;
            if (! $visible) {
                continue;
            }

            $routeName = self::resolveRouteName($key, $resource);

            // Skip if route doesn't exist
            if (! $routeName || ! Route::has($routeName)) {
                continue;
            }

            $patterns = self::resolveActivePatterns($key, $routeName, $resource);

            // Check if current route matches any pattern for this resource
            if ($request) {
                foreach ($patterns as $pattern) {
                    if ($request->routeIs($pattern)) {
                        // Found active resource, return its key
                        return $key;
                    }
                }
            }
        }

        // Fallback: if on main CMS route, return "dashboard"
        if ($request && $request->routeIs('cms.dashboard')) {
            return 'dashboard';
        }

        return null;
    }
}
