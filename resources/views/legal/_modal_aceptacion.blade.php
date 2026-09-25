@php
    $versionTerminos = \App\Models\ConfiguracionSistema::obtener('legal.terminos.version', '1.0');
    $tituloTerminos  = \App\Models\ConfiguracionSistema::obtener('legal.terminos.titulo', 'Términos y Condiciones');
    $textoTerminos   = \App\Models\ConfiguracionSistema::obtener('legal.terminos.texto', '');

    $versionAviso    = \App\Models\ConfiguracionSistema::obtener('legal.aviso_privacidad.version', '1.0');
    $tituloAviso     = \App\Models\ConfiguracionSistema::obtener('legal.aviso_privacidad.titulo', 'Aviso de Privacidad');
    $textoAviso      = \App\Models\ConfiguracionSistema::obtener('legal.aviso_privacidad.texto', '');
@endphp

<div class="modal fade" id="modalAceptarLegal" tabindex="-1" data-bs-backdrop="static"
     data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-cabosync-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-shield-lock-fill"></i>
                    Aceptación obligatoria de Términos y Aviso de Privacidad
                </h5>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Debes leer y aceptar ambos documentos</strong> antes de continuar usando CaboSync.
                    Hasta que aceptes, no podrás navegar en la plataforma.
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab"
                                data-bs-target="#tabTerminos" type="button" role="tab">
                            <i class="bi bi-file-earmark-text"></i>
                            Términos y Condiciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab"
                                data-bs-target="#tabAviso" type="button" role="tab">
                            <i class="bi bi-shield-check"></i>
                            Aviso de Privacidad
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabTerminos" role="tabpanel">
                        <div class="legal-scroll" data-legal="terminos">
                            <h5>{{ $tituloTerminos }}
                                <span class="badge bg-secondary">v{{ $versionTerminos }}</span>
                            </h5>
                            {!! $textoTerminos !!}
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input legal-check" type="checkbox"
                                   id="checkTerminos" data-legal="terminos" disabled>
                            <label class="form-check-label small" for="checkTerminos">
                                He leído y acepto los <strong>Términos y Condiciones</strong>.
                            </label>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabAviso" role="tabpanel">
                        <div class="legal-scroll" data-legal="aviso">
                            <h5>{{ $tituloAviso }}
                                <span class="badge bg-secondary">v{{ $versionAviso }}</span>
                            </h5>
                            {!! $textoAviso !!}
                        </div>
                        <div class="form-check mt-3">
                            <input class="form-check-input legal-check" type="checkbox"
                                   id="checkAviso" data-legal="aviso" disabled>
                            <label class="form-check-label small" for="checkAviso">
                                He leído y acepto el <strong>Aviso de Privacidad</strong>.
                            </label>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info small mt-3 mb-0">
                    <i class="bi bi-info-circle"></i>
                    Se registrará tu aceptación con fecha, hora, IP y dispositivo.
                    Podrás consultar tu historial en
                    <a href="{{ route('legal.misConsentimientos') }}" target="_blank">
                        Mis consentimientos
                    </a>.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-cabosync-primary" id="btnAceptarLegal" disabled>
                    <i class="bi bi-check-lg"></i> Aceptar y continuar
                </button>
            </div>
        </div>
    </div>
</div>