import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
      outDir: 'public/build',
    },
    theme: {
        extend: {
          fontFamily: {
            sans: ['Roboto', 'ui-sans-serif', 'system-ui'],
            slab: ['Roboto Slab', 'ui-serif', 'serif'],
          },
        },
      },
});
