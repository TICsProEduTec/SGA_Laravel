<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;


class ReporteController extends Controller
{
    public function index()
    {
        $profesor = Auth::user();

        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $profesor->id_user_moodle,
        ]);

        if ($response->failed()) {
            Log::error('Error al obtener cursos para reporte', ['response' => $response->body()]);
            return back()->withErrors(['moodle' => 'No se pudieron obtener los cursos del profesor.']);
        }

        $cursos = collect($response->json());
        $datos = [];

        foreach ($cursos as $curso) {
            $enrolled = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken' => config('services.moodle.token'),
                'wsfunction' => 'core_enrol_get_enrolled_users',
                'moodlewsrestformat' => 'json',
                'courseid' => $curso['id'],
            ]);

            if ($enrolled->failed()) continue;

            $usuarios = collect($enrolled->json());

            $estudiantes = $usuarios->filter(fn($u) =>
                collect($u['roles'])->pluck('roleid')->contains(5)
            );

            $lista = [];

            foreach ($estudiantes as $e) {
                $notas = Http::asForm()->post(config('services.moodle.endpoint'), [
                    'wstoken' => config('services.moodle.token'),
                    'wsfunction' => 'gradereport_user_get_grade_items',
                    'moodlewsrestformat' => 'json',
                    'courseid' => $curso['id'],
                    'userid' => $e['id'],
                ]);

                $json = $notas->json();

                $promedio = 0;
                if (isset($json['usergrades'][0]['gradeitems'])) {
                    $total = collect($json['usergrades'][0]['gradeitems'])->firstWhere('itemtype', 'course');
                    $promedio = $total['graderaw'] ?? 0;
                }

                $lista[] = [
                    'id' => $e['id'],
                    'fullname' => $e['fullname'] ?? $e['firstname'] . ' ' . $e['lastname'],
                    'promedio' => number_format($promedio, 2),
                ];
            }

            $datos[] = [
                'curso' => $curso['fullname'],
                'id' => $curso['id'],
                'estudiantes' => $lista,
            ];
        }

        return view('profesor.reporte.index', compact('datos'));
    }

    public function verTareas($userId, $cursoId)
    {
        try {
            // Agregar log para verificar los parámetros
            Log::info("Verificando tareas para el usuario: {$userId} en el curso: {$cursoId}");

            // Buscar usuario local por id_user_moodle
            $user = User::where('id_user_moodle', $userId)->first();

            // Comprobar si no se encuentra el usuario
            if (!$user) {
                Log::error("Usuario con id_user_moodle {$userId} no encontrado");
                return redirect()->route('profesor.reporte.index')->withErrors(['error' => 'Usuario no encontrado.']);
            }

            // Buscar curso local por id_curso_moodle
            $curso = Curso::where('id_curso_moodle', $cursoId)->firstOrFail();

            // Llamada al webservice para obtener el contenido del curso y las actividades
            $resp = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken'            => config('services.moodle.token'),
                'wsfunction'         => 'core_course_get_contents',
                'moodlewsrestformat' => 'json',
                'courseid'           => (int)$curso->id_curso_moodle,
            ]);

            // Log para ver si la respuesta del webservice es correcta
            Log::info("Respuesta del webservice para el curso {$cursoId}: ", $resp->json());

            // Verificar si la respuesta es exitosa
            if ($resp->failed()) {
                Log::error("Error al obtener el contenido del curso {$cursoId}", ['response' => $resp->body()]);
                return redirect()->route('profesor.reporte.index')->withErrors(['error' => 'Error al obtener el contenido del curso.']);
            }

            $data = $resp->json();

            // Filtrar las actividades relevantes (por ejemplo, tareas)
            $tareas = [];
            foreach ($data as $section) {
                foreach ($section['modules'] as $module) {
                    if ($module['modname'] === 'assign') {
                        $tareas[] = [
                            'name' => $module['name'],
                            'id' => $module['instance'],
                            'url' => $module['url'],
                        ];
                    }
                }
            }

            // Log de las tareas que se han encontrado
            Log::info("Tareas encontradas para el curso {$cursoId}: ", $tareas);

            // Si no hay tareas, se muestra un mensaje
            if (count($tareas) == 0) {
                Log::warning("No se encontraron tareas para el curso {$cursoId}");
                return redirect()->route('profesor.reporte.index')->withErrors(['error' => 'No se encontraron tareas para este curso.']);
            }

            // Mostrar la vista con las tareas
            return view('profesor.reporte.tareas', compact('user', 'curso', 'tareas'));

        } catch (\Exception $e) {
            Log::error("Error en el controlador verTareas: " . $e->getMessage());
            return response($e->getMessage(), 500);
        }
    }





    public function generarRecursoPdf($cursoId)
    {
        $profesor = Auth::user();

        // Obtener los cursos asignados al profesor
        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $profesor->id_user_moodle,
        ]);

        // Verificar si la solicitud fue exitosa
        if ($response->failed()) {
            return back()->withErrors(['moodle' => 'Error al obtener los cursos del profesor.']);
        }

        // Obtener el curso correspondiente al ID
        $courses = collect($response->json());
        $curso = $courses->firstWhere('id', $cursoId);

        // Si no se encuentra el curso
        if (!$curso) {
            return back()->withErrors(['error' => 'Curso no encontrado']);
        }

        // Obtener los estudiantes matriculados en el curso
        $enrolledResponse = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_enrolled_users',
            'moodlewsrestformat' => 'json',
            'courseid' => $curso['id'],
        ]);

        $usuarios = collect($enrolledResponse->json());

        // Filtrar los estudiantes con el rol 'estudiante' (roleid 5)
        $estudiantes = $usuarios->filter(function ($u) {
            return collect($u['roles'])->pluck('roleid')->contains(5); // Estudiantes
        });

        // Si no hay estudiantes
        if ($estudiantes->isEmpty()) {
            return back()->withErrors(['error' => 'No hay estudiantes matriculados en este curso.']);
        }

        // Obtener las notas de los estudiantes
        $estudiantesConNotas = $estudiantes->map(function ($estudiante) use ($curso) {
            $notasResponse = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken' => config('services.moodle.token'),
                'wsfunction' => 'gradereport_user_get_grade_items',
                'moodlewsrestformat' => 'json',
                'courseid' => $curso['id'],
                'userid' => $estudiante['id'],
            ]);

            $notasData = $notasResponse->json();

            $promedio = 0;
            if (isset($notasData['usergrades'][0]['gradeitems'])) {
                $total = collect($notasData['usergrades'][0]['gradeitems'])->firstWhere('itemtype', 'course');
                $promedio = $total['graderaw'] ?? 0;
            }

            return [
                'fullname' => $estudiante['fullname'],
                'email' => $estudiante['email'],
                'promedio' => number_format($promedio, 2),
            ];
        });

        // Obtener la fecha actual para el reporte
        $fecha = now()->format('d-m-Y');

        // Generar el PDF utilizando la vista "profesor.reporte.pdf"
        $pdf = Pdf::loadView('profesor.reporte.pdf', [
            'curso' => $curso['fullname'],
            'shortname' => $curso['shortname'],
            'estudiantes' => $estudiantesConNotas,
            'fecha' => $fecha,
        ]);

        // Retornar el PDF como descarga
        return $pdf->download("reporte_notas_{$curso['shortname']}.pdf");
    }



}
