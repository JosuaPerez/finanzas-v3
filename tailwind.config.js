import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                shrink: {
                    '0%':   { width: '100%' },
                    '100%': { width: '0%' },
                },
            },
            animation: {
                // matches the 4-second toast auto-dismiss window
                'shrink': 'shrink 4s linear forwards',
            },
        },
    },

    plugins: [forms],
};