import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // TODO: Re-add 'resources/css/filament/admin/theme.css'
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: true,
        port: 5173,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        hmr: {
            host: 'localhost',
            port: 5173,
            protocol: 'ws',
        },
    },
});
