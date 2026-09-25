<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UsuarioBienvenidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $usuario;
    public string $passwordTemporal;
    public string $creadoPor;
    public bool $esReset;

    public function __construct(User $usuario, string $passwordTemporal, string $creadoPor, bool $esReset = false)
    {
        $this->usuario          = $usuario;
        $this->passwordTemporal = $passwordTemporal;
        $this->creadoPor        = $creadoPor;
        $this->esReset          = $esReset;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->esReset
                ? 'Contraseña restablecida - CaboSync'
                : 'Bienvenido a CaboSync - Credenciales de acceso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.usuario-bienvenida',
            with: [
                'usuario'          => $this->usuario,
                'passwordTemporal' => $this->passwordTemporal,
                'creadoPor'        => $this->creadoPor,
                'esReset'          => $this->esReset,
                'urlLogin'         => route('login'),
            ],
        );
    }
}