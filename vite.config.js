import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/chat.js'],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'firebase': ['firebase/app', 'firebase/firestore', 'firebase/database'],
                    'alpine': ['alpinejs'],
                }
            }
        },
        chunkSizeWarningLimit: 600,
    },
});