@props([
    'data' => [],
    'height' => 36,
    'width' => 120,
    'stroke' => '#6366f1',
    'fill' => 'rgba(99, 102, 241, 0.14)',
    'strokeWidth' => 1.6,
    'showFill' => true,
])

@php
    $values = collect($data)->map(fn($v) => is_numeric($v) ? (float) $v : 0)->values()->all();
    $count = count($values);
    if ($count < 2) {
        $values = $count === 1 ? [$values[0], $values[0]] : [0, 0];
        $count = 2;
    }
    $min = min($values);
    $max = max($values);
    $range = $max - $min;
    $padding = 2;
    $usableHeight = $height - $padding * 2;
    $stepX = $count > 1 ? ($width / ($count - 1)) : $width;

    $points = [];
    foreach ($values as $i => $v) {
        $x = round($i * $stepX, 2);
        $y = $range == 0
            ? round($height / 2, 2)
            : round($padding + ($usableHeight - (($v - $min) / $range) * $usableHeight), 2);
        $points[] = "$x,$y";
    }

    $linePath = 'M ' . implode(' L ', $points);
    $areaPath = $linePath . " L {$width},{$height} L 0,{$height} Z";

    // Last value indicator
    $lastPoint = end($points);
    [$lastX, $lastY] = explode(',', $lastPoint);
@endphp

<svg
    class="pb-spark inline-block"
    viewBox="0 0 {{ $width }} {{ $height }}"
    preserveAspectRatio="none"
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $width }}"
    height="{{ $height }}"
    aria-hidden="true"
>
    @if($showFill)
        <path d="{{ $areaPath }}" fill="{{ $fill }}" stroke="none"/>
    @endif
    <path d="{{ $linePath }}" fill="none" stroke="{{ $stroke }}" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
    <circle cx="{{ $lastX }}" cy="{{ $lastY }}" r="2.5" fill="{{ $stroke }}"/>
    <circle cx="{{ $lastX }}" cy="{{ $lastY }}" r="5" fill="{{ $stroke }}" opacity="0.18"/>
</svg>
