// ============================================
// CABOSYNC - GEOLOCALIZACIÓN GLOBAL v5
// Se ejecuta en TODAS las vistas autenticadas.
// - Bloquea la app sin permiso
// - Detecta cambios en tiempo real
// - Vigila periódicamente
// ============================================

(function () {
    'use strict';

    // =========================================================
    // CONFIGURACIÓN
    // =========================================================
    const CONFIG = {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0,
        precisionMaxima: 500,
        reintentosMax: 3,
        intervaloVigilancia: 5000,
    };

    // =========================================================
    // ESTADO
    // =========================================================
    let ubicacion = {
        lat: null,
        lng: null,
        precision: null,
        timestamp: null,
        disponible: false,
        imprecisa: false,
    };

    let estadoPermiso   = 'unknown';
    let bloqueado       = false;
    let watchInterval   = null;
    let permisoRef      = null;
    let inicializado    = false;
    let overlayActivo   = false;

    // =========================================================
    // DETECTAR ESTADO DEL PERMISO
    // =========================================================
    async function detectarEstadoPermiso() {
        if (!navigator.permissions) {
            estadoPermiso = 'unknown';
            return estadoPermiso;
        }

        try {
            const permiso = await navigator.permissions.query({ name: 'geolocation' });
            estadoPermiso = permiso.state;
            permisoRef = permiso;

            permiso.onchange = () => {
                const nuevo = permiso.state;
                console.info('[Geo] Permiso cambió:', estadoPermiso, '→', nuevo);
                estadoPermiso = nuevo;

                if (nuevo === 'granted') {
                    solicitarConReintentos()
                        .then(() => ocultarBloqueo())
                        .catch(() => mostrarBloqueo('No pudimos obtener tu ubicación. Reintenta.'));
                } else if (nuevo === 'denied') {
                    mostrarBloqueo();
                }
            };

            return estadoPermiso;
        } catch (e) {
            console.warn('[Geo] Permissions API no disponible:', e.message);
            return 'unknown';
        }
    }

    // =========================================================
    // SOLICITAR UBICACIÓN
    // =========================================================
    function solicitarUbicacion() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject({ message: 'Geolocalización no soportada', code: -1 });
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const precision = Math.round(pos.coords.accuracy);
                    const imprecisa = precision > CONFIG.precisionMaxima;

                    ubicacion = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        precision: precision,
                        timestamp: pos.timestamp,
                        disponible: true,
                        imprecisa: imprecisa,
                    };

                    console.info('[Geo] ✓ Ubicación capturada:', {
                        lat: ubicacion.lat.toFixed(6),
                        lng: ubicacion.lng.toFixed(6),
                        precision: `${precision}m`,
                        calidad: imprecisa ? '⚠️ IMPRECISA' : '✓ Buena',
                    });

                    resolve(ubicacion);
                },
                (error) => {
                    const mensaje = {
                        1: 'Permiso denegado',
                        2: 'Ubicación no disponible',
                        3: 'Timeout',
                    }[error.code] || 'Error desconocido';

                    console.warn('[Geo] ✗ Error:', mensaje);
                    ubicacion.disponible = false;
                    reject({ message: mensaje, code: error.code });
                },
                {
                    enableHighAccuracy: CONFIG.enableHighAccuracy,
                    timeout: CONFIG.timeout,
                    maximumAge: CONFIG.maximumAge,
                }
            );
        });
    }

    async function solicitarConReintentos() {
        let intento = 0;
        let ultimoError = null;

        while (intento < CONFIG.reintentosMax) {
            try {
                return await solicitarUbicacion();
            } catch (err) {
                ultimoError = err;
                intento++;

                if (err.code === 1) break;

                if (intento < CONFIG.reintentosMax) {
                    console.info(`[Geo] Reintento ${intento}/${CONFIG.reintentosMax}...`);
                    await new Promise(r => setTimeout(r, 1500));
                }
            }
        }

        throw ultimoError;
    }

    // =========================================================
    // VIGILANCIA CONTINUA (todas las vistas)
    // =========================================================
    function iniciarVigilancia() {
        detenerVigilancia();

        watchInterval = setInterval(async () => {
            // 1. Verificar estado del permiso
            if (navigator.permissions) {
                try {
                    const permiso = await navigator.permissions.query({ name: 'geolocation' });

                    if (permiso.state !== estadoPermiso) {
                        estadoPermiso = permiso.state;

                        if (permiso.state === 'granted') {
                            solicitarConReintentos()
                                .then(() => ocultarBloqueo())
                                .catch(() => {});
                        } else if (permiso.state === 'denied') {
                            mostrarBloqueo();
                        }
                    }
                } catch (e) { /* Silencioso */ }
            }

            // 2. Verificar que la ubicación siga disponible
            if (estadoPermiso === 'granted' && !ubicacion.disponible) {
                solicitarConReintentos()
                    .then(() => ocultarBloqueo())
                    .catch(() => { /* Silencioso */ });
            }

            // 3. Si está bloqueado y el permiso está granted, intentar desbloquear
            if (bloqueado && estadoPermiso === 'granted') {
                try {
                    await solicitarConReintentos();
                    ocultarBloqueo();
                } catch (e) { /* Silencioso */ }
            }
        }, CONFIG.intervaloVigilancia);
    }

    function detenerVigilancia() {
        if (watchInterval) {
            clearInterval(watchInterval);
            watchInterval = null;
        }
    }

    // =========================================================
    // OVERLAY DE BLOQUEO
    // =========================================================
    function mostrarBloqueo(mensajePersonalizado = null) {
        if (overlayActivo) return;
        overlayActivo = true;
        bloqueado = true;

        // Si ya existe, no duplicar
        if (document.getElementById('geoBlockOverlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'geoBlockOverlay';
        overlay.style.cssText = `
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: rgba(30, 81, 128, 0.95);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: geoFadeIn 0.3s ease;
        `;

        const mensaje = mensajePersonalizado || `
            Para usar <strong>CaboSync</strong> necesitamos que actives tu ubicación para generar la <strong>trazabilidad</strong>.
        `;

        overlay.innerHTML = `
            <style>
                @keyframes geoFadeIn { from { opacity: 0; } to { opacity: 1; } }
                @keyframes geoPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
                @keyframes geoSpin { to { transform: rotate(360deg); } }
            </style>
            <div style="
                background: #fff;
                border-radius: 16px;
                max-width: 520px;
                width: 100%;
                padding: 40px 32px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.4);
                animation: geoFadeIn 0.4s ease;
            ">
                <div style="
                    width: 88px; height: 88px; margin: 0 auto 24px;
                    background: linear-gradient(135deg, #1E5180, #4a90e2);
                    border-radius: 50%;
                    display: flex; align-items: center; justify-content: center;
                    animation: geoPulse 2s ease-in-out infinite;
                    box-shadow: 0 8px 24px rgba(30, 81, 128, 0.3);
                ">
                    <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" fill="#fff" viewBox="0 0 16 16">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                </div>

                <h2 style="
                    color: #1E5180;
                    font-size: 1.55rem;
                    font-weight: 700;
                    margin: 0 0 12px 0;
                    letter-spacing: 0.5px;
                ">
                    📍 Ubicación requerida
                </h2>

                <p style="
                    color: #666;
                    font-size: 0.95rem;
                    line-height: 1.6;
                    margin: 0 0 24px 0;
                ">
                    ${mensaje}
                </p>

                <div style="
                    background: #f8f9fa;
                    border-left: 4px solid #F28C28;
                    padding: 14px 16px;
                    border-radius: 8px;
                    text-align: left;
                    font-size: 0.85rem;
                    color: #333;
                    line-height: 1.7;
                    margin-bottom: 24px;
                ">
                    <strong>🔧 Cómo activar la ubicación:</strong>
                    <ol style="margin: 8px 0 0 0; padding-left: 20px;">
                        <li>Haz clic en el <strong>candado 🔒</strong> al lado de la URL.</li>
                        <li>Busca <strong>"Ubicación"</strong>.</li>
                        <li>Cambia a <strong>"Permitir"</strong>.</li>
                    </ol>
                    <p style="margin: 10px 0 0 0; font-size: 0.8rem; color: #666;">
                        💡 Si no aparece el candado, busca el ícono <strong>ⓘ</strong> o <strong>⚙️</strong> al inicio de la barra de direcciones.
                    </p>
                </div>

                <button id="geoRetryBtn" style="
                    background: #1E5180;
                    color: #fff;
                    border: none;
                    padding: 14px 32px;
                    font-size: 1rem;
                    font-weight: 600;
                    border-radius: 8px;
                    cursor: pointer;
                    width: 100%;
                    transition: all 0.2s;
                    letter-spacing: 0.5px;
                ">
                    🔄 Reintentar ahora
                </button>

                <p style="
                    color: #adb5bd;
                    font-size: 0.75rem;
                    margin: 16px 0 0 0;
                ">
                    La app se desbloqueará <strong>automáticamente</strong> cuando concedas el permiso.
                </p>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const btn = document.getElementById('geoRetryBtn');
        btn.addEventListener('mouseover', () => btn.style.background = '#F28C28');
        btn.addEventListener('mouseout', () => btn.style.background = '#1E5180');

        btn.addEventListener('click', async () => {
            btn.disabled = true;
            btn.innerHTML = '<span style="display:inline-block; animation: geoSpin 1s linear infinite;">⏳</span> Solicitando...';

            try {
                await detectarEstadoPermiso();
                await solicitarConReintentos();
                ocultarBloqueo();
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = '🔄 Reintentar ahora';
            }
        });
    }

    function ocultarBloqueo() {
        bloqueado = false;
        overlayActivo = false;

        const overlay = document.getElementById('geoBlockOverlay');
        if (overlay) {
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
                document.body.style.overflow = '';
            }, 300);
        }
    }

    // =========================================================
    // INICIALIZACIÓN GLOBAL
    // =========================================================
    async function inicializar() {
        if (inicializado) return;
        inicializado = true;

        console.info('[Geo] Inicializando geolocalización global...');

        const estado = await detectarEstadoPermiso();
        iniciarVigilancia();

        if (estado === 'granted') {
            try {
                await solicitarConReintentos();
                ocultarBloqueo();
            } catch (err) {
                mostrarBloqueo('No pudimos obtener tu ubicación. Verifica que el GPS esté activado.');
            }
            return;
        }

        if (estado === 'denied') {
            mostrarBloqueo();
            return;
        }

        // 'prompt' | 'unknown'
        try {
            await solicitarConReintentos();
            ocultarBloqueo();
        } catch (err) {
            if (err.code === 1) {
                mostrarBloqueo();
            } else {
                mostrarBloqueo('No pudimos obtener tu ubicación. Reintenta.');
            }
        }
    }

    // =========================================================
    // API PÚBLICA
    // =========================================================
    window.CaboSyncGeo = {
        obtener: () => ubicacion.disponible ? {
            lat: ubicacion.lat,
            lng: ubicacion.lng,
            precision: ubicacion.precision,
        } : null,

        obtenerConCalidad: () => ({
            ...ubicacion,
            estadoPermiso,
            bloqueado,
        }),

        obtenerEstadoPermiso: () => estadoPermiso,
        estaBloqueado: () => bloqueado,

        obtenerDeviceId: () => {
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

                let hash = 0;
                for (let i = 0; i < fingerprint.length; i++) {
                    hash = ((hash << 5) - hash) + fingerprint.charCodeAt(i);
                    hash |= 0;
                }

                deviceId = 'dev_' + Math.abs(hash).toString(36);
                localStorage.setItem('cabosync_device_id', deviceId);
            }
            return deviceId;
        },

        inicializar,

        refrescar: async () => {
            await detectarEstadoPermiso();
            return await solicitarConReintentos();
        },

        garantizar: async () => {
            if (ubicacion.disponible) return true;
            try {
                await solicitarConReintentos();
                return true;
            } catch (err) {
                mostrarBloqueo();
                return false;
            }
        },

        mostrarBloqueo,
        ocultarBloqueo,
    };

    // =========================================================
    // AUTO-INICIALIZACIÓN
    // =========================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }

    // =========================================================
    // INTERCEPTOR AXIOS GLOBAL
    // =========================================================
    if (window.axios) {
        window.axios.interceptors.request.use((config) => {
            const geo = window.CaboSyncGeo.obtener();
            const deviceId = window.CaboSyncGeo.obtenerDeviceId();

            if (config.method && ['post', 'put', 'delete', 'patch'].includes(config.method.toLowerCase())) {
                config.headers = config.headers || {};

                if (geo) {
                    config.headers['X-Geo-Lat'] = geo.lat;
                    config.headers['X-Geo-Lng'] = geo.lng;
                    config.headers['X-Geo-Precision'] = geo.precision;
                }

                config.headers['X-Device-Id'] = deviceId;
            }

            return config;
        });
    }

    // =========================================================
    // INTERCEPTOR GLOBAL PARA NAVEGACIÓN DE PÁGINAS
    // (Antes de navegar, verifica que tenga geo)
    // =========================================================
    document.addEventListener('click', async (e) => {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');

        // Ignorar externos, anchors, etc.
        if (!href ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:') ||
            link.target === '_blank' ||
            (href.startsWith('http') && !href.includes(window.location.hostname))) {
            return;
        }

        // Si está bloqueado, no permitir navegar
        if (window.CaboSyncGeo.estaBloqueado()) {
            e.preventDefault();
            e.stopPropagation();
            window.CaboSyncGeo.mostrarBloqueo();
            return false;
        }
    }, true); // capture phase para interceptar antes

})();