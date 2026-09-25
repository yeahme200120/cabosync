import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pase_lista.css',
                'resources/css/conciliacion.css',
                'resources/css/reportes.css',
                'resources/css/usuarios.css', 
                'resources/js/app.js',
                'resources/js/reportes.js',
                'resources/js/geo.js',
                'resources/js/usuarios.js', 
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});