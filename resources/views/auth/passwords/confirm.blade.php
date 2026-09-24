@extends('layouts.app')

@section('title', 'Confirmar Contraseña')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-shield-lock-fill"></i> Confirmar Contraseña</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Por seguridad, confirma tu contraseña antes de continuar.</p>

                    <form method="POST" action="{{ route('password.confirm') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label-cabosync">Contraseña</label>
                            <input id="password" type="password"
                                class="form-control form-control-cabosync @error('password') is-invalid @enderror"
                                name="password" required autocomplete="current-password" autofocus>

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn-cabosync-primary w-100">
                            CONFIRMAR
                        </button>

                        @if (Route::has('password.request'))
                            <div class="text-center mt-3">
                                <a href="{{ route('password.request') }}" class="text-muted small">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection