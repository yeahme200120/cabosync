<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BitacoraAccion;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Reset the given user's password.
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->save();

        // Registrar en bitácora
        BitacoraAccion::create([
            'usuario_id'   => $user->id,
            'accion'       => 'password.reset',
            'descripcion'  => "Contraseña restablecida vía correo electrónico para {$user->email}",
            'direccion_ip' => request()->ip(),
            'navegador'    => request()->userAgent(),
            'created_at'   => now(),
        ]);

        event(new \Illuminate\Auth\Events\PasswordReset($user));

        $this->guard()->login($user);
    }
}