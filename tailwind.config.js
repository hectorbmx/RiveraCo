import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
          // ✅ agrega esto
    './resources/js/**/*.js',
    './resources/js/**/*.ts',
    ],
  safelist: [
    // ✅ lo que estás usando en ese botón
    'bg-rose-600',
    'hover:bg-rose-700',
    'text-white',
    'px-3',
    'py-1.5',
    'rounded-lg',
    'text-xs',
  ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
