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
                // "fb" is just the token name from an earlier Facebook-blue pass --
                // it now carries the app's own purple/periwinkle brand color.
                fb: {
                    50: '#EEF0FD',
                    100: '#E0E3FB',
                    200: '#C2C7F5',
                    300: '#A3A9EE',
                    400: '#8B90F0',
                    500: '#767CEA',
                    600: '#6C6FEB',
                    700: '#5457D1',
                    800: '#4547AC',
                    900: '#38398A',
                    bg: '#F5F4FC',
                },
                gold: {
                    DEFAULT: '#F5B942',
                    dark: '#E0A62E',
                    light: '#FDF1DA',
                },
            },
        },
    },

    plugins: [forms],
};
