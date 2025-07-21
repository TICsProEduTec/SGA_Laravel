<?php

namespace App\Http\Controllers;

use App\Models\Contenido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContenidoController extends Controller
{
    public function index()
    {
        $docente = Auth::user();

        // Verifica que sea un profesor
        if ($docente->rol !== 'profesor') {
            abort(403, 'Acceso no autorizado.');
        }

        // Obtener cursos del docente desde Moodle
        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $docente->id_user_moodle,
        ]);

        $cursos = $response->successful() ? $response->json() : [];

        // Obtener recursos subidos por este docente
        $contenidos = Contenido::where('user_id', $docente->id)->latest()->get();

        return view('profesor.recursos.index', compact('contenidos', 'cursos'));
    }

    public function store(Request $request)
    {
        Log::info('📝 Ingresando a ContenidoController@store');

        // Validar entrada
        $request->validate([
            'curso_moodle_id' => 'required|numeric',
            'curso_nombre' => 'required|string|max:255',
            'archivo' => 'required|file|mimes:pdf|max:10240',
        ]);

        $docente = Auth::user();
        Log::info('👤 Docente autenticado:', ['id' => $docente->id, 'name' => $docente->name]);

        if (!$request->hasFile('archivo')) {
            Log::error('❌ No se recibió el archivo');
            return back()->withErrors(['archivo' => 'No se recibió ningún archivo.']);
        }

        $file = $request->file('archivo');

        if (!$file->isValid()) {
            Log::error('❌ El archivo no es válido.');
            return back()->withErrors(['archivo' => 'El archivo subido no es válido.']);
        }

        // Preparar nombre limpio y carpeta
        $nombreOriginal = $file->getClientOriginalName();
        $nombreLimpio = time() . '_' . Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '.pdf';
        $rutaRelativa = 'contenido/' . $docente->id;
        $rutaCompleta = storage_path('app/public/' . $rutaRelativa);

        Log::info('📄 Preparando guardado manual:', [
            'original' => $nombreOriginal,
            'limpio' => $nombreLimpio,
            'rutaCompleta' => $rutaCompleta,
        ]);

        try {
            // Asegurar carpeta
            if (!file_exists($rutaCompleta)) {
                mkdir($rutaCompleta, 0775, true);
            }

            // Guardar archivo manualmente con move()
            $file->move($rutaCompleta, $nombreLimpio);

            $rutaFinal = $rutaRelativa . '/' . $nombreLimpio;

            if (!file_exists(storage_path('app/public/' . $rutaFinal))) {
                Log::error("❌ Archivo no fue guardado en: " . $rutaFinal);
                return back()->withErrors(['archivo' => 'El archivo no se pudo guardar en disco.']);
            }

            Log::info('✅ Archivo guardado correctamente en: ' . $rutaFinal);

            // Guardar en base de datos
            Contenido::create([
                'user_id' => $docente->id,
                'curso_moodle_id' => $request->curso_moodle_id,
                'curso_nombre' => $request->curso_nombre,
                'archivo' => $rutaFinal,
            ]);

            Log::info('✅ Registro en base de datos creado correctamente.');

            return redirect()->back()->with('success', '✅ Recurso subido correctamente.');
        } catch (\Throwable $e) {
            Log::error('💥 Excepción al guardar contenido:', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['archivo' => '❌ Ocurrió un error interno al guardar el recurso.']);
        }
    }

    public function destroy($id)
    {
        $docente = Auth::user();

        $contenido = Contenido::where('id', $id)->where('user_id', $docente->id)->first();

        if (!$contenido) {
            abort(404, 'Recurso no encontrado.');
        }

        try {
            $archivoPath = storage_path('app/public/' . $contenido->archivo);

            // Eliminar archivo si existe
            if (file_exists($archivoPath)) {
                unlink($archivoPath);
                Log::info('🗑️ Archivo eliminado:', ['path' => $archivoPath]);
            } else {
                Log::warning('⚠️ Archivo no encontrado para eliminar:', ['path' => $archivoPath]);
            }

            // Eliminar registro en base de datos
            $contenido->delete();

            return redirect()->back()->with('success', '🗑️ Recurso eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('💥 Error al eliminar recurso:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['archivo' => '❌ Ocurrió un error al eliminar el recurso.']);
        }
    }

}
