// ============================================
// CABOSYNC - APP.JS GLOBAL
// ============================================

import './bootstrap';
import Swal from 'sweetalert2';
import axios from 'axios';
import * as XLSX from 'xlsx';

// Cropper.js v2 (Custom Elements)
import 'cropperjs';

window.Swal = Swal;
window.axios = axios;

window.XLSX = XLSX;

// ============================================
// SWEETALERT2 - HELPERS GLOBALES CABOSYNC
// ============================================
window.CaboSyncAlert = {
    primary: '#1E5180',
    secondary: '#F28C28',

    success: function(mensaje) {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000, timerProgressBar: true,
            didOpen: (t) => {
                t.addEventListener('mouseenter', Swal.stopTimer);
                t.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'success', title: mensaje });
    },

    error: function(mensaje) {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end',
            showConfirmButton: false, timer: 4000, timerProgressBar: true,
            didOpen: (t) => {
                t.addEventListener('mouseenter', Swal.stopTimer);
                t.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: 'error', title: mensaje });
    },

    warning: function(mensaje) {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000, timerProgressBar: true,
        });
        Toast.fire({ icon: 'warning', title: mensaje });
    },

    info: function(mensaje) {
        const Toast = Swal.mixin({
            toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000, timerProgressBar: true,
        });
        Toast.fire({ icon: 'info', title: mensaje });
    },

    confirm: async function(titulo, texto = '', opciones = {}) {
        const defaults = {
            title: titulo, text: texto, icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: opciones.confirmButtonColor || this.primary,
            cancelButtonColor: '#6c757d',
            confirmButtonText: opciones.confirmText || 'Sí, confirmar',
            cancelButtonText: opciones.cancelText || 'Cancelar',
            reverseButtons: true,
        };
        const result = await Swal.fire({ ...defaults, ...opciones });
        return result.isConfirmed;
    },

    successModal: function(titulo, texto = '') {
        return Swal.fire({
            title: titulo, text: texto, icon: 'success',
            confirmButtonColor: this.primary,
            confirmButtonText: 'Entendido',
        });
    },

    loading: function(mensaje = 'Procesando...') {
        Swal.fire({
            title: mensaje,
            allowOutsideClick: false, allowEscapeKey: false,
            didOpen: () => { Swal.showLoading(); }
        });
    },

    close: function() { Swal.close(); }
};

// ============================================
// PRELOADER GLOBAL CABOSYNC
// ============================================
window.CaboSyncLoader = {
    show: function(mensaje = 'Cargando...') {
        const preloader = document.getElementById('cabosync-preloader');
        if (!preloader) return;
        const text = preloader.querySelector('.cabosync-preloader__text');
        if (text) text.textContent = mensaje;
        preloader.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    },
    hide: function() {
        const preloader = document.getElementById('cabosync-preloader');
        if (!preloader) return;
        preloader.style.display = 'none';
        document.body.style.overflow = '';
    }
};

// ============================================
// AXIOS - CONFIGURACIÓN GLOBAL
// ============================================
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

let peticionesActivas = 0;

axios.interceptors.request.use(
    config => {
        peticionesActivas++;
        CaboSyncLoader.show();
        return config;
    },
    error => {
        peticionesActivas = Math.max(0, peticionesActivas - 1);
        if (peticionesActivas === 0) CaboSyncLoader.hide();
        return Promise.reject(error);
    }
);

axios.interceptors.response.use(
    response => {
        peticionesActivas = Math.max(0, peticionesActivas - 1);
        if (peticionesActivas === 0) CaboSyncLoader.hide();
        return response;
    },
    error => {
        peticionesActivas = Math.max(0, peticionesActivas - 1);
        if (peticionesActivas === 0) CaboSyncLoader.hide();

        if (error.response) {
            const status = error.response.status;
            const data = error.response.data;

            if (status === 419) {
                CaboSyncAlert.error('Sesión expirada. Recarga la página.');
            } else if (status === 403) {
                CaboSyncAlert.error(data.error || data.message || 'No tienes permiso para esta acción');
            } else if (status === 422) {
                const errors = data.errors || {};
                const primerError = Object.values(errors)[0]?.[0] || data.message || 'Datos inválidos';
                CaboSyncAlert.error(primerError);
            } else if (status === 500) {
                CaboSyncAlert.error('Error interno del servidor. Contacta a soporte.');
            } else if (status !== 401) {
                CaboSyncAlert.error(data.error || data.message || 'Ocurrió un error inesperado');
            }
        } else if (error.request) {
            CaboSyncAlert.error('No hay conexión con el servidor');
        } else {
            CaboSyncAlert.error('Error al procesar la solicitud');
        }

        return Promise.reject(error);
    }
);

// ============================================
// PRELOADER PARA NAVEGACIÓN ENTRE PÁGINAS
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Mostrar preloader al hacer clic en links internos
    document.body.addEventListener('click', (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');
        const target = link.getAttribute('target');

        // Ignorar: vacíos, externos, target="_blank", anchors, javascript:
        if (
            !href ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:') ||
            target === '_blank' ||
            href.startsWith('http') && !href.includes(window.location.hostname)
        ) {
            return;
        }

        // Ignorar si es una petición AJAX (deja que Axios maneje el preloader)
        if (link.dataset.ajax === 'true') return;

        // Mostrar preloader
        CaboSyncLoader.show('Cargando página...');
    });

    // Ocultar preloader al cargar la nueva página
    window.addEventListener('pageshow', () => {
        CaboSyncLoader.hide();
    });
});