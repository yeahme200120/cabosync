// ============================================
// CABOSYNC - GEOLOCALIZACIÓN
// Captura lat/lon del navegador y las adjunta
// a cada petición Axios.
// ============================================

window.CaboSyncGeo = (function () {
    let ubicacion = {
        lat: null,
        lng: null,
        precision: null,
        disponible: false,
    };

    /**
     * Solicita la ubicación al navegador (una sola vez por sesión).
     * Si el usuario deniega, sigue funcionando sin geo.
     */
    function inicializar() {
        if (!navigator.geolocation) {
            console.warn('Geolocalización no soportada');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                ubicacion = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    precision: pos.coords.accuracy,
                    disponible: true,
                };
                console.info('Geolocalización capturada:', ubicacion);
            },
            (error) => {
                console.warn('Geolocalización denegada:', error.message);
                ubicacion.disponible = false;
            },
            {
                enableHighAccuracy: false,
                timeout: 8000,
                maximumAge: 600000, // 10 min
            }
        );
    }

    /**
     * Devuelve la ubicación actual (o null si no está disponible).
     */
    function obtener() {
        return ubicacion.disponible ? {
            lat: ubicacion.lat,
            lng: ubicacion.lng,
            precision: ubicacion.precision,
        } : null;
    }

    /**
     * Genera un device_id estable (fingerprint del navegador).
     * NO es la MAC address real, pero sirve como identificador.
     */
    function obtenerDeviceId() {
        let deviceId = localStorage.getItem('cabosync_device_id');

        if (!deviceId) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = '14px Arial';
            ctx.fillText('CaboSync', 2, 2);

            const fingerprint = [
                navigator.userAgent,
                navigator.language,
                screen.width + 'x' + screen.height,
                new Date().getTimezoneOffset(),
                canvas.toDataURL().slice(-50),
            ].join('|');

            // Hash simple
            let hash = 0;
            for (let i = 0; i < fingerprint.length; i++) {
                hash = ((hash << 5) - hash) + fingerprint.charCodeAt(i);
                hash |= 0;
            }

            deviceId = 'dev_' + Math.abs(hash).toString(36);
            localStorage.setItem('cabosync_device_id', deviceId);
        }

        return deviceId;
    }

    // Inicializar al cargar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }

    return {
        obtener,
        obtenerDeviceId,
        inicializar,
    };
})();

// ============================================
// INTERCEPTOR AXIOS: adjuntar geo a cada request
// ============================================
if (window.axios) {
    axios.interceptors.request.use((config) => {
        const geo = window.CaboSyncGeo?.obtener();
        const deviceId = window.CaboSyncGeo?.obtenerDeviceId();

        // Solo adjuntar si es POST/PUT/DELETE (acciones que modifican)
        if (config.method && ['post', 'put', 'delete', 'patch'].includes(config.method.toLowerCase())) {
            config.headers = config.headers || {};

            if (geo) {
                config.headers['X-Geo-Lat'] = geo.lat;
                config.headers['X-Geo-Lng'] = geo.lng;
                config.headers['X-Geo-Precision'] = geo.precision;
            }

            if (deviceId) {
                config.headers['X-Device-Id'] = deviceId;
            }
        }

        return config;
    });
}