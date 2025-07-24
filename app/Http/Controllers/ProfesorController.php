<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Area;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProfesorController extends Controller
{
    public function index()
    {
        $profesor = Auth::user();
        
        if ($profesor->rol !== 'profesor') {
            return redirect()->route('home')->withErrors(['moodle' => 'No tienes permisos para ver esta sección.']);
        }

        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $profesor->id_user_moodle,
        ]);

        if (!$response->successful()) {
            Log::error('Error al conectar con Moodle', ['response' => $response->body()]);
            return back()->withErrors(['moodle' => 'Error al conectar con Moodle']);
        }

        $courses = $response->json();

        if (empty($courses)) {
            return view('profesor.index', compact('profesor'))->with('message', 'No tienes cursos asignados.');
        }

        $courseIds = collect($courses)->pluck('id')->toArray();

        $materias = Area::whereIn('id_course_moodle', $courseIds)
                        ->with('grado')
                        ->get();

        return view('profesor.index', compact('profesor', 'courses', 'materias'));
    }

    public function cursos()
    {
        $profesor = Auth::user();

        if ($profesor->rol !== 'profesor') {
            Log::error('Acceso no autorizado para el usuario', ['user' => $profesor]);
            return redirect()->route('home')->withErrors(['moodle' => 'Acceso no autorizado.']);
        }

        $userid = $profesor->id_user_moodle;

        if (!$userid) {
            Log::error('ID de Moodle no encontrado para el usuario', ['user' => $profesor]);
            return back()->withErrors(['moodle' => 'El ID de Moodle no está configurado correctamente.']);
        }

        $response = Http::withOptions([
                    'verify' => false,
                ])
                ->timeout(120)
                ->asForm()
                ->post(config('services.moodle.endpoint'), [
                    'wstoken' => config('services.moodle.token'),
                    'wsfunction' => 'core_enrol_get_users_courses',
                    'moodlewsrestformat' => 'json',
                    'userid' => $userid,
                ]);

        if ($response->failed()) {
            Log::error('Error al obtener los cursos de Moodle', ['response' => $response->body()]);
            return back()->withErrors(['moodle' => 'Error al obtener los cursos de Moodle. Verifique los parámetros.']);
        }

        $courses = $response->json();

        if (isset($courses['exception']) && $courses['exception'] == 'core\\exception\\invalid_parameter_exception') {
            Log::error('Error de parámetros inválidos en la solicitud a Moodle', ['response' => $courses]);
            return back()->withErrors(['moodle' => 'Parámetros inválidos en la solicitud a Moodle']);
        }

        if (empty($courses)) {
            return view('profesor.cursos.index', compact('profesor'))->with('message', 'No tienes cursos asignados.');
        }

        return view('profesor.cursos.index', compact('profesor', 'courses'));
    }

    public function verMatriculas()
    {
        $profesor = Auth::user();

        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $profesor->id_user_moodle,
        ]);

        if ($response->failed()) {
            Log::error('Error al obtener cursos del profesor', ['response' => $response->body()]);
            return back()->withErrors(['moodle' => 'No se pudieron obtener los cursos.']);
        }

        $courses = collect($response->json());

        $matriculas = [];

        foreach ($courses as $course) {
            $enrolledResponse = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken' => config('services.moodle.token'),
                'wsfunction' => 'core_enrol_get_enrolled_users',
                'moodlewsrestformat' => 'json',
                'courseid' => $course['id'],
            ]);

            if ($enrolledResponse->successful()) {
                $users = collect($enrolledResponse->json());

                $estudiantes = $users->filter(function ($user) {
                    return collect($user['roles'])->pluck('roleid')->contains(5);
                });

                $matriculas[] = [
                    'id' => $course['id'], // ✅ NECESARIO para el botón PDF
                    'curso' => $course['fullname'],
                    'shortname' => $course['shortname'],
                    'estudiantes' => $estudiantes,
                ];
            }
        }

        return view('profesor.matriculas.index', compact('matriculas'));
    }

    public function descargarMatriculasPdf($courseId)
    {
        $profesor = Auth::user();

        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $profesor->id_user_moodle,
        ]);

        $courses = collect($response->json());
        $curso = $courses->firstWhere('id', (int) $courseId);

        if (!$curso) {
            return back()->withErrors(['error' => 'No tienes acceso a este curso.']);
        }

        $usersResponse = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_enrolled_users',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId,
        ]);

        $usuarios = collect($usersResponse->json());

        $estudiantes = $usuarios->filter(function ($u) {
            return collect($u['roles'])->pluck('roleid')->contains(5); // Estudiantes
        });

        // Establecer la zona horaria y el idioma
        Carbon::setLocale('es');
        $fecha = Carbon::now('America/Guayaquil')->translatedFormat('d \d\e F \d\e Y');

        $pdf = Pdf::loadView('profesor.matriculas.pdf', [
        'curso' => $curso['fullname'],
        'shortname' => $curso['shortname'],
        'estudiantes' => $estudiantes,
        'fecha' => $fecha,
        ]);

        return $pdf->download("matriculas_curso_{$curso['shortname']}.pdf");
    }

}
