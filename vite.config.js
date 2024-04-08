import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// let publicDirectory = '../../public/vendor/alt-cookies-addon'
// if (process.env.NODE_ENV === 'production' || true) {
//     publicDirectory = 'resources/dist'
// }

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/alt-cookies-addon.js',
                'resources/css/alt-cookies-addon.css'
            ],
            publicDirectory: 'resources/dist',
        }),
    ]
});
