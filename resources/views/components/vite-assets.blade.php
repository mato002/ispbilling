@props([
    'assets' => ['resources/css/app.css', 'resources/js/app.js'],
])

@php
    $hotFile = public_path('hot');
    $manifestPath = public_path('build/manifest.json');
    $useDevServer = file_exists($hotFile) && app()->environment('local');
@endphp

@if ($useDevServer)
    @vite($assets)
@elseif (is_readable($manifestPath))
    @php
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $stylesheets = [];
        $scripts = [];
        $visited = [];

        $walk = static function (string $key) use (&$walk, $manifest, &$stylesheets, &$scripts, &$visited): void {
            if (isset($visited[$key])) {
                return;
            }

            $visited[$key] = true;
            $entry = $manifest[$key] ?? null;

            if (! is_array($entry)) {
                return;
            }

            foreach ($entry['imports'] ?? [] as $import) {
                $walk($import);
            }

            foreach ($entry['css'] ?? [] as $css) {
                $stylesheets[$css] = true;
            }

            if (! isset($entry['file'])) {
                return;
            }

            if (str_ends_with($entry['file'], '.css')) {
                $stylesheets[$entry['file']] = true;

                return;
            }

            if (str_ends_with($entry['file'], '.js')) {
                $scripts[] = $entry['file'];
            }
        };

        foreach ($assets as $asset) {
            $walk($asset);
        }
    @endphp

    @foreach (array_keys($stylesheets) as $css)
        <link rel="stylesheet" href="{{ asset('build/'.$css) }}" />
    @endforeach

    @foreach ($scripts as $js)
        <script type="module" src="{{ asset('build/'.$js) }}"></script>
    @endforeach
@endif
