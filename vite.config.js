// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/notifications-invoice.js',
                // Pastikan path ke file tema benar
                'vendor/nuxtifyts/dash-stack-theme/resources/css/theme.css',
            ],
            refresh: true,
        }),
    ],
});