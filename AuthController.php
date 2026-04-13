<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Show the login form.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     * Note: We need to authenticate using 'Usuario' field, not the primary key (PWD).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'Usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        Log::info('Login attempt', [
            'usuario' => $credentials['Usuario'],
            'has_password' => !empty($credentials['password'])
        ]);

        // IMPORTANTE: Buscar usuario por campo 'Usuario' ya que la PK es 'PWD'
        $user = \App\Models\User::where('Usuario', $credentials['Usuario'])->first();

        if (!$user) {
            Log::warning('User not found', ['usuario' => $credentials['Usuario']]);
            return back()->withErrors([
                'Usuario' => 'Las credenciales proporcionadas no son válidas.',
            ])->onlyInput('Usuario');
        }

        Log::info('User found', [
            'usuario' => $user->Usuario,
            'pwd_hash' => substr($user->PWD, 0, 20) . '...'
        ]);

        // Verificar si el usuario está activo
        if (!$user->Activo) {
            Log::warning('Inactive user attempt', ['usuario' => $credentials['Usuario']]);
            return back()->withErrors([
                'Usuario' => 'Su cuenta está inactiva. Contacte al administrador.',
            ])->onlyInput('Usuario');
        }

        // Verificar la contraseña manualmente
        if (\Illuminate\Support\Facades\Hash::check($credentials['password'], $user->PWD)) {
            // Autenticar manualmente al usuario
            Auth::login($user, $request->filled('remember'));
            
            Log::info('Login successful', ['usuario' => $user->Usuario]);
            
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        Log::warning('Invalid password', ['usuario' => $credentials['Usuario']]);
        return back()->withErrors([
            'Usuario' => 'Las credenciales proporcionadas no son válidas.',
        ])->onlyInput('Usuario');
    }

    /**
     * Handle a logout request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}