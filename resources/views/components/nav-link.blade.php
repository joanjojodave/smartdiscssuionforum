@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium leading-5 bg-fb-600 text-white focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium leading-5 text-gray-500 hover:text-fb-700 hover:bg-fb-50 focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
