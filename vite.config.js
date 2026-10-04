import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // فایل توسعه نباید داخل بستهٔ استقرار قرار بگیرد یا در production خوانده شود.
            hotFile: 'storage/framework/vite.hot',
            refresh: true,
        }),
    ],
});
