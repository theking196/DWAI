import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel([
            'resources/views/pages/*.blade.php',
            'resources/views/layouts/*.blade.php',
        ]),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    css: {
        devSourcemap: true,
        minimize: true,
    },
    build: {
        rollupOptions: {
            output: {
                manualInline: ['twig'],
            },
        },
    },
});