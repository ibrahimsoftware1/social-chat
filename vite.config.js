import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'; // <-- add Vue plugin
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(), // <-- register Vue plugin
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js', // <-- enable runtime + template compiler
        },
    },
});
