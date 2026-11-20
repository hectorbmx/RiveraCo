import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig(({ command }) => ({
    base: command === 'build' ? './' : '/v2/public/',
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
}));
