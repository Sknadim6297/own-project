import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    // Bind IPv4 explicitly. Default [::1] breaks auth CSS when the app is opened via 127.0.0.1.
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/tazen.css',
                'resources/css/auth-theme.css',
                'resources/css/professional-panel.css',
                'resources/css/customer-panel.css',
                'resources/css/yuktione-feedback.css',
                'resources/js/yuktione-feedback.js',
                'resources/js/customer-registration.js',
                'resources/js/professional-registration.js',
                'resources/js/tazen-v2/panel.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
