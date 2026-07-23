@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-fb-500 focus:ring-fb-500 rounded-md shadow-sm']) }}>
