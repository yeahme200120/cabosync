@component('mail::message')

# Hola {{ $user->nombre ?? 'Usuario' }},

Recibimos una solicitud para restablecer la contraseña de tu cuenta en **CaboSync**.

@component('mail::button', ['url' => $actionUrl, 'color' => 'primary'])
Restablecer Contraseña
@endcomponent

Este enlace expirará en **60 minutos**.

Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual seguirá siendo válida.

---

**ID SOFTWARE HOUSE**  
Sistema CaboSync - Gestión de Asistencia y Personal  
Cuautla, Morelos, México  
[Política de Privacidad]({{ config('app.url') }}/aviso-privacidad)

<small>Este es un correo automático, por favor no respondas a este mensaje.</small>

@endcomponent