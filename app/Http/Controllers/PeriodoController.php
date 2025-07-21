<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PeriodoController extends Controller
{
    private $token;
    private $domainname;

    public function __construct()
    {
        // Usamos las variables del archivo .env
        $this->token = config('services.moodle.token');
        $this->domainname = config('services.moodle.endpoint');
    }

    /** Mostrar listado de periodos */
    public function index()
    {
        $periodos = Periodo::withCount('grados')->get();
        return view('periodos.index', compact('periodos'));
    }

    /** Mostrar formulario de creación */
    public function create()
    {
        return view('periodos.create');
    }

    /** Crear un nuevo periodo y su categoría en Moodle */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Crear categoría en Moodle
        $response = Http::asForm()->post($this->domainname, [
            'wstoken' => $this->token,
            'wsfunction' => 'core_course_create_categories',
            'moodlewsrestformat' => 'json',
            'categories[0][name]' => $request->input('name'),
            'categories[0][parent]' => 0,
            'categories[0][descriptionformat]' => 0,
        ]);

        $data = $response->json();
        Log::info('Moodle create category response:', $data);

        if (!isset($data[0]['id'])) {
            return back()->with('error', 'No se pudo crear la categoría en Moodle.');
        }

        // Crear periodo localmente
        $periodo = new Periodo();
        $periodo->name = $request->name;
        $periodo->id_category_moodle = $data[0]['id'];
        $periodo->save();

        return redirect()->route('periodos.index')->with('info', 'El periodo se creó correctamente.');
    }

    /** Mostrar formulario de edición */
    public function edit(Periodo $periodo)
    {
        return view('periodos.edit', compact('periodo'));
    }

    /** Actualizar nombre del periodo y categoría Moodle */
    public function update(Request $request, Periodo $periodo)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Actualizar en Moodle
        Http::asForm()->post($this->domainname, [
            'wstoken' => $this->token,
            'wsfunction' => 'core_course_update_categories',
            'moodlewsrestformat' => 'json',
            'categories[0][id]' => $periodo->id_category_moodle,
            'categories[0][name]' => $request->name,
            'categories[0][descriptionformat]' => 0,
        ]);

        // Actualizar localmente
        $periodo->name = $request->name;
        $periodo->save();

        return redirect()->route('periodos.index')->with('info', 'El periodo se actualizó correctamente.');
    }

    /** Eliminar periodo y su categoría en Moodle */
    public function destroy(Periodo $periodo)
    {
        // Eliminar en Moodle
        Http::asForm()->post($this->domainname, [
            'wstoken' => $this->token,
            'wsfunction' => 'core_course_delete_categories',
            'moodlewsrestformat' => 'json',
            'categories[0][id]' => $periodo->id_category_moodle,
            'categories[0][newparent]' => 0,
            'categories[0][recursive]' => 1,
        ]);

        // Eliminar localmente
        $periodo->delete();

        return redirect()->route('periodos.index')->with('info', 'El periodo se eliminó correctamente.');
    }

    /** Vista para consultar grados del periodo */
    public function consultargrados(Periodo $periodo)
    {
        $periodo->load('grados.users');
        return view('periodos.consultargrados', compact('periodo'));
    }
}
