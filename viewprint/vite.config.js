import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    optimizeDeps: {
        // Pre-bundle heavy dependencies
        include: ['@niivue/niivue', 'lodash', 'hotkeys-js'],
    },
    build: {
        // Increase chunk size warning limit for Niivue
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                // Manual chunks for better caching
                manualChunks: {
                    'niivue': ['@niivue/niivue'],
                    'vendor': ['lodash', 'eventemitter3', 'file-saver', 'hotkeys-js'],
                },
            },
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Watch for changes in Blade files
            ignored: ['**/storage/**', '**/node_modules/**'],
        },
    },
});
