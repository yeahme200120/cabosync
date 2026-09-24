@extends('layouts.app')

@section('title', 'Verificar Correo')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-envelope-check-fill"></i> Verifica tu correo electrónico</h5>
                </div>
                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill"></i> Te hemos enviado un nuevo enlace de verificación.
                        </div>
                    @endif

                    <p class="mb-3">
                        Antes de continuar, revisa tu correo electrónico para encontrar el enlace de verificación.
                    </p>

                    <p class="mb-3">
                        Si no recibiste el correo,
                        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                                haz clic aquí para solicitar otro
                            </button>.
                        </form>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection