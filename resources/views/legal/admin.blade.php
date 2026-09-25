@extends('layouts.app')

@section('title', 'Configuración Legal')

@push('styles')
    @vite(['resources/css/legal.css'])
@endpush

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="mb-0 text-cabosync-primary">
                    <i class="bi bi-shield-lock"></i> Configuración Legal
                </h2>
                <small class="text-muted">
                    Textos de Términos y Aviso de Privacidad (vista previa pública disponible)
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('legal.terminos') }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-up-right"></i> Ver Términos públicos
                </a>
                <a href="{{ route('legal.aviso') }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-box-arrow-up-right"></i> Ver Aviso público
                </a>
            </div>
        </div>

        <form id="formLegal">
            @csrf

            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab"
                            data-bs-target="#tabConfigTerminos" type="button">
                        <i class="bi bi-file-earmark-text"></i> Términos y Condiciones
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab"
                            data-bs-target="#tabConfigAviso" type="button">
                        <i class="bi bi-shield-check"></i> Aviso de Privacidad
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tabConfigTerminos">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Versión</label>
                                    <input type="text" name="terminos_version"
                                           value="{{ $datos['terminos_version'] }}"
                                           class="form-control" required maxlength="20">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label small fw-bold">Título</label>
                                    <input type="text" name="terminos_titulo"
                                           value="{{ $datos['terminos_titulo'] }}"
                                           class="form-control" required maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">
                                        Contenido (HTML permitido)
                                    </label>
                                    <textarea name="terminos_texto" rows="20"
                                              class="form-control font-monospace"
                                              style="font-size: 0.85rem;" required>{{ $datos['terminos_texto'] }}</textarea>
                                    <small class="text-muted">
                                        Puedes usar HTML: &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tabConfigAviso">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Versión</label>
                                    <input type="text" name="aviso_version"
                                           value="{{ $datos['aviso_version'] }}"
                                           class="form-control" required maxlength="20">
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label small fw-bold">Título</label>
                                    <input type="text" name="aviso_titulo"
                                           value="{{ $datos['aviso_titulo'] }}"
                                           class="form-control" required maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">
                                        Contenido (HTML permitido)
                                    </label>
                                    <textarea name="aviso_texto" rows="20"
                                              class="form-control font-monospace"
                                              style="font-size: 0.85rem;" required>{{ $datos['aviso_texto'] }}</textarea>
                                    <small class="text-muted">
                                        Puedes usar HTML: &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info small mt-3">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Importante:</strong> al cambiar el número de <strong>versión</strong>, todos los usuarios deberán
                <strong>re-aceptar</strong> los Términos y el Aviso en su siguiente inicio de sesión.
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-x-lg"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-cabosync-primary" id="btnGuardarLegal">
                    <i class="bi bi-check-lg"></i> <span>Guardar cambios</span>
                </button>
            </div>
        </form>

    </div>
@endsection

@push('scripts')
    @vite(['resources/js/legal.js'])
@endpush