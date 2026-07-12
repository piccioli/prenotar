import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/gr/theme.css',
                'resources/css/filament/sezione/theme.css',
            ],
            refresh: true,
        }),
    ],
});
