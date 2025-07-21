<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        if (Auth::user()->email !== 'admin@colegiopceirafaelgaleth.com') {
            abort(403, 'Acceso no autorizado');
        }

        $user = Auth::user(); // Añade esta línea
        return view('admin.dashboard', compact('user'));
    }

}
