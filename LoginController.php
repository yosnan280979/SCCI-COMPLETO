<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function username()
    {
        return 'Usuario';
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        // Buscar usuario por nombre de usuario
        $user = User::where('Usuario', $request->{$this->username()})->first();
        
        if (!$user) {
            \Log::error('Usuario no encontrado: ' . $request->{$this->username()});
            return false;
        }
        
        \Log::info('Usuario encontrado: ' . $user->Usuario);
        \Log::info('Hash almacenado: ' . $user->PWD);
        \Log::info('Hash comienza con: ' . substr($user->PWD, 0, 10));
        
        // Verificar que el hash sea válido para Bcrypt
        if (strpos($user->PWD, '$2y$') === 0) {
            \Log::info('Hash parece ser Bcrypt');
            if (Hash::check($request->password, $user->PWD)) {
                \Log::info('Contraseña correcta');
                Auth::login($user);
                return true;
            } else {
                \Log::error('Hash::check falló');
            }
        } else {
            \Log::error('Hash no es Bcrypt: ' . $user->PWD);
        }
        
        return false;
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}