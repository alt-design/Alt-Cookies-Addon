import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/frontend-manager.js',
                'resources/js/alt-cookies-addon.js',
                'resources/css/alt-cookies-addon.css',
            ],
            publicDirectory: 'resources/dist',
        }),
    ]
});
