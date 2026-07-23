import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                fb: {
                    50: '#E7F3FF',
                    100: '#CFE4FF',
                    200: '#A8CCFF',
                    300: '#7EB2FF',
                    400: '#4E96FF',
                    500: '#2E7CF6',
                    600: '#1877F2',
                    700: '#166FE5',
                    800: '#0F5DC4',
                    900: '#0A4A9E',
                    bg: '#F0F2F5',
                },
            },
        },
    },

    plugins: [forms],
};
