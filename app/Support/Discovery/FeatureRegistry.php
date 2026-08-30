<?php

namespace App\Support\Discovery;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class FeatureRegistry
{
    /**
     * @return list<array<string, mixed>>
     */
    public function index(?string $scope = null): array
    {
        $scope ??= $this->scopeForUser();
        $entries = [];

        foreach (config("navigation_features.{$scope}", []) as $item) {
            $entry = $this->makeEntry($item, $scope);

            if ($entry !== null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $query, ?string $scope = null, int $limit = 24): array
    {
        $tokens = $this->expandQueryTokens($this->tokenize($query));

        if ($tokens === []) {
            return [];
        }

        $results = array_values(array_filter(
            $this->index($scope),
            fn (array $entry) => $this->entryMatchesTokens($entry, $tokens),
        ));

        return array_slice(array_map(fn (array $entry) => $this->serializeForClient($entry), $results), 0, $limit);
    }

    public function scopeForUser(): string
    {
        $user = auth()->user();

        if ($user?->isTechnician()) {
            return 'technician';
        }

        if ($user?->isSuperAdmin()) {
            return 'admin';
        }

        return 'isp';
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function makeEntry(array $item, string $scope): ?array
    {
        $route = $item['route'] ?? null;

        if (! is_string($route) || ! Route::has($route)) {
            return null;
        }

        $label = (string) ($item['label'] ?? '');
        $section = (string) ($item['section'] ?? '');
        $pathSegments = array_values(array_filter([$section, $label]));
        $keywords = $this->buildKeywords($label, $section, $item['keywords'] ?? []);

        $params = is_array($item['params'] ?? null) ? $item['params'] : [];
        $url = route($route, $params);
        if (! empty($item['hash']) && is_string($item['hash'])) {
            $url .= '#'.ltrim($item['hash'], '#');
        }

        return [
            'id' => hash('sha256', implode('|', [$scope, $route, $label, json_encode($params)])),
            'label' => $label,
            'description' => (string) ($item['description'] ?? ''),
            'path' => implode(' → ', $pathSegments),
            'path_segments' => $pathSegments,
            'category' => (string) ($item['category'] ?? 'navigation'),
            'keywords' => $keywords,
            'search_text' => implode(' ', $keywords),
            'url' => $url,
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    public function serializeForClient(array $entry): array
    {
        return [
            'id' => $entry['id'],
            'label' => $entry['label'],
            'description' => $entry['description'] ?? '',
            'path' => $entry['path'],
            'url' => $entry['url'],
            'category' => $entry['category'] ?? 'navigation',
        ];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  list<string>  $tokens
     */
    protected function entryMatchesTokens(array $entry, array $tokens): bool
    {
        $haystack = strtolower((string) ($entry['search_text'] ?? ''));

        foreach ($tokens as $token) {
            if (! str_contains($haystack, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    protected function tokenize(string $value): array
    {
        $normalized = Str::lower(Str::ascii($value));
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? '';
        $parts = preg_split('/\s+/', trim($normalized)) ?: [];

        return array_values(array_filter($parts, fn (string $part) => $part !== ''));
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    protected function expandQueryTokens(array $tokens): array
    {
        $expanded = $tokens;
        $aliases = config('feature_registry.keyword_aliases', []);

        foreach ($tokens as $token) {
            foreach ($aliases[$token] ?? [] as $alias) {
                $expanded = array_merge($expanded, $this->tokenize((string) $alias));
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @param  list<string>  $extra
     * @return list<string>
     */
    protected function buildKeywords(string $label, string $section, array $extra): array
    {
        $tokens = array_merge(
            $this->tokenize($label),
            $this->tokenize($section),
        );

        foreach ($extra as $keyword) {
            $tokens = array_merge($tokens, $this->tokenize((string) $keyword));
        }

        $aliases = config('feature_registry.keyword_aliases', []);

        foreach ($tokens as $token) {
            foreach ($aliases[$token] ?? [] as $alias) {
                $tokens = array_merge($tokens, $this->tokenize((string) $alias));
            }
        }

        return array_values(array_unique($tokens));
    }
}
