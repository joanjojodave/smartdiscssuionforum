@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-2 w-full ps-3 pe-4 py-2 text-start text-base font-medium text-fb-700 bg-fb-50 rounded-md focus:outline-none focus:text-fb-800 focus:bg-fb-100 transition duration-150 ease-in-out'
            : 'flex items-center gap-2 w-full ps-3 pe-4 py-2 text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-md focus:outline-none focus:text-gray-800 focus:bg-gray-50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
