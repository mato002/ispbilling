import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                pb: {
                    navy: '#0F172A',
                    indigo: '#312E81',
                    blue: '#2563EB',
                    cyan: '#06B6D4',
                },
            },
            boxShadow: {
                'pb-card': '0 1px 0 rgba(255,255,255,0.6) inset, 0 8px 24px -12px rgba(15,23,42,0.12), 0 2px 6px -2px rgba(15,23,42,0.06)',
                'pb-card-dark': '0 1px 0 rgba(255,255,255,0.04) inset, 0 12px 32px -16px rgba(0,0,0,0.6), 0 2px 6px -2px rgba(0,0,0,0.4)',
            },
        },
    },

    plugins: [forms],
};
