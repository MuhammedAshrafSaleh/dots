@props([
    'name',
    'size' => 20,
    'stroke' => 1.5,
    'flip' => null,
])
@php
    // Mirror of design/website/src/components/core/Icon.jsx.
    // Outlined Lucide icon, 1.5px stroke, round caps/joins; directional icons flip in RTL.
    $aliases = [
        'home' => 'house',
        'alert-circle' => 'circle-alert',
        'check-circle' => 'circle-check',
        'alert-triangle' => 'triangle-alert',
        'whatsapp' => 'message-circle',
    ];
    $directional = ['chevron-right', 'chevron-left', 'arrow-right', 'arrow-left', 'arrow-up-right', 'move-right', 'undo-2', 'redo-2', 'log-out', 'list-filter'];

    $key = $aliases[$name] ?? $name;
    $mirror = $flip ?? (\App\Support\Locale::isRtl() && in_array($key, $directional, true));

    $classes = trim('d-icon '.$attributes->get('class', ''));
    $style = trim(($mirror ? 'transform: scaleX(-1);' : '').' '.$attributes->get('style', ''));

    $svgAttributes = array_merge(
        $attributes->except(['class', 'style'])->getAttributes(),
        [
            'width' => $size,
            'height' => $size,
            'stroke-width' => $stroke,
            'aria-hidden' => 'true',
        ],
        $style !== '' ? ['style' => $style] : [],
    );
@endphp
{!! svg('lucide-'.$key, $classes, $svgAttributes)->toHtml() !!}
