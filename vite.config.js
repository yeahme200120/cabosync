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
                'resources/js/empresas.js',
                'resources/css/obras.css',
                'resources/css/bitacora.css',
                'resources/css/legal.css',

                'resources/js/app.js',
                'resources/js/reportes.js',
                'resources/js/geo.js',
                'resources/js/usuarios.js', 
                'resources/css/empresas.css', 
                'resources/js/obras.js',
                'resources/js/bitacora.js',
                'resources/js/legal.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});