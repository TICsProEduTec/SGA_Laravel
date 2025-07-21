<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Usuario no registrado.']);
        }

        // 🚫 Bloquear estudiantes
        if ($user->rol === 'estudiante') {
            return back()->withErrors(['email' => 'Acceso no permitido para estudiantes.']);
        }

        // ADMIN (login por correo y Auth)
        if ($user->email === 'admin@colegiopceirafaelgaleth.com') {
            if (Auth::attempt($credentials, $request->filled('remember'))) {
                $user->rol = 'admin'; // Fuerza el rol solo si no está asignado
                $user->save();
                return redirect()->route('admin.dashboard');
            }
            return back()->withErrors(['email' => 'Credenciales incorrectas.']);
        }

        // PROFESOR (login especial)
        if ($user->rol === 'profesor') {
            $expectedPassword = 'P' . strtolower(substr($user->name, 0, 1)) . $user->cedula . '*';

            if ($credentials['password'] === $expectedPassword) {
                Auth::login($user);
                return redirect()->route('profesor.dashboard');
            }

            return back()->withErrors(['email' => 'Contraseña incorrecta para profesor.']);
        }

        return back()->withErrors(['email' => 'Rol no autorizado.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }
}

