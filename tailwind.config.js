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
            colors: {
                primary: {
                    50: '#EDF7FF',
                    100: '#D6ECFF',
                    200: '#B5DFFF',
                    300: '#83CCFF',
                    400: '#48B0FF',
                    500: '#1E8EFF',
                    600: '#0671FF',
                    700: '#0061FF',
                    800: '#084BC5',
                    900: '#0D439B',
                    950: '#0E295D',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
