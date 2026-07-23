@props(['name'])

@php
$paths = [
    'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 9.5 12 3l9 6.5M5 9v10a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9" />',
    'chat' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 5.5A2.5 2.5 0 0 1 5.5 3h13A2.5 2.5 0 0 1 21 5.5v8a2.5 2.5 0 0 1-2.5 2.5H9l-5 4v-4H5.5A2.5 2.5 0 0 1 3 13.5v-8Z" />',
    'message' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v7a2.5 2.5 0 0 1-2.5 2.5H10l-4 3.5V16H6.5A2.5 2.5 0 0 1 4 13.5v-7Z" /><circle cx="8.5" cy="10" r=".9" fill="currentColor" stroke="none" /><circle cx="12" cy="10" r=".9" fill="currentColor" stroke="none" /><circle cx="15.5" cy="10" r=".9" fill="currentColor" stroke="none" />',
    'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2" /><path stroke-linecap="round" d="M3.5 9.5h17M8 3v4M16 3v4" />',
    'calendar-plus' => '<rect x="3.5" y="5" width="17" height="15" rx="2" /><path stroke-linecap="round" d="M3.5 9.5h17M8 3v4M16 3v4M12 12.5v5M9.5 15h5" />',
    'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10M12 20V4M20 20v-7" />',
    'shield' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z" />',
    'users' => '<circle cx="9" cy="8" r="3" /><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5" /><circle cx="17" cy="9" r="2.3" /><path stroke-linecap="round" stroke-linejoin="round" d="M15.7 14.2c2.2.3 3.8 2 3.8 4.3" />',
    'user' => '<circle cx="12" cy="8" r="3.2" /><path stroke-linecap="round" stroke-linejoin="round" d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6" />',
    'bell' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 9a6 6 0 1 1 12 0c0 3.5 1 5.5 1.6 6.4a.8.8 0 0 1-.7 1.3H5.1a.8.8 0 0 1-.7-1.3C5 14.5 6 12.5 6 9Z" /><path stroke-linecap="round" d="M9.5 18a2.5 2.5 0 0 0 5 0" />',
][$name] ?? '';
@endphp

<svg {{ $attributes->merge(['class' => 'w-4 h-4', 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8']) }}>
    {!! $paths !!}
</svg>
