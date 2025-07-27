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

                if (!empty($json['usergrades'][0]['gradeitems'])) {
                    $sumaNotas = 0;
                    $cuentaNotas = 0;

                    foreach ($json['usergrades'][0]['gradeitems'] as $item) {
                        if (
                            $item['itemtype'] === 'mod' &&
                            isset($item['graderaw']) &&
                            isset($item['grademax']) &&
                            floatval($item['grademax']) > 0
                        ) {
                            $raw = floatval($item['graderaw']);
                            $max = floatval($item['grademax']);
                            $normalizada = ($raw / $max) * 10;
                            $sumaNotas += $normalizada;
                            $cuentaNotas++;
                        }
                    }

                    if ($cuentaNotas > 0) {
                        $promedio = round($sumaNotas / $cuentaNotas, 2);
                    }
                }

                $estado = $promedio >= 7 ? 'Aprobado' : 'Reprobado';

                $lista[] = [
                    'id' => $e['id'],
                    'fullname' => $e['fullname'] ?? $e['firstname'] . ' ' . $e['lastname'],
                    'promedio' => $promedio,
                    'estado' => $estado,
                ];
            }

            $datos[] = [
                'curso' => $curso['fullname'],
                'shortname' => $curso['shortname'],
                'id' => $curso['id'],
                'estudiantes' => $lista,
            ];
        }

        return view('profesor.reporte.index', compact('datos'));
    }



    public function verTareas($userId, $cursoId)
    {
        try {
            Log::info("Verificando tareas para el usuario: {$userId} en el curso: {$cursoId}");

            $user = User::where('id_user_moodle', $userId)->first();
            if (!$user) {
                Log::error("Usuario con id_user_moodle {$userId} no encontrado");
                return redirect()->route('profesor.reporte.index')->withErrors(['error' => 'Usuario no encontrado.']);
            }

            $curso = Curso::where('id_curso_moodle', $cursoId)->firstOrFail();

            // Obtener contenido del curso
            $resp = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken'            => config('services.moodle.token'),
                'wsfunction'         => 'core_course_get_contents',
                'moodlewsrestformat' => 'json',
                'courseid'           => (int)$curso->id_curso_moodle,
            ]);

            if ($resp->failed()) {
                Log::error("Error al obtener el contenido del curso {$cursoId}", ['response' => $resp->body()]);
                return redirect()->route('profesor.reporte.index')->withErrors(['error' => 'Error al obtener el contenido del curso.']);
            }

            $data = $resp->json();

            // Obtener calificaciones del estudiante
            $gradeResp = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken'            => config('services.moodle.token'),
                'wsfunction'         => 'gradereport_user_get_grade_items',
                'moodlewsrestformat' => 'json',
                'courseid'           => $cursoId,
                'userid'             => $userId,
            ]);

            $gradeItems = $gradeResp->json()['usergrades'][0]['gradeitems'] ?? [];

            $tareas = [];

            foreach ($data as $section) {
                foreach ($section['modules'] as $module) {
                    if ($module['modname'] === 'assign') {
                        $nota = 'Sin calificar';

                        foreach ($gradeItems as $item) {
                            if (
                                $item['itemtype'] === 'mod' &&
                                Str::lower(trim($item['itemname'])) === Str::lower(trim($module['name'])) &&
                                isset($item['graderaw']) &&
                                isset($item['grademax']) &&
                                floatval($item['grademax']) > 0
                            ) {
                                $raw = floatval($item['graderaw']);
                                $max = floatval($item['grademax']);
                                $escala10 = round(($raw / $max) * 10, 2);
                                $nota = number_format($escala10, 2);
                                break;
                            }
                        }

                        $tareas[] = [
                            'name' => $module['name'],
                            'nota' => $nota,
                        ];
                    }
                }
            }

            if (count($tareas) == 0) {
                Log::warning("No se encontraron tareas para el curso {$cursoId}");
                return redirect()->route('profesor.reporte.index')->withErrors(['error' => 'No se encontraron tareas para este curso.']);
            }

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

        if ($response->failed()) {
            return back()->withErrors(['moodle' => 'Error al obtener los cursos del profesor.']);
        }

        // Obtener el curso
        $courses = collect($response->json());
        $curso = $courses->firstWhere('id', $cursoId);

        if (!$curso) {
            return back()->withErrors(['error' => 'Curso no encontrado']);
        }

        // Obtener estudiantes
        $enrolledResponse = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_enrolled_users',
            'moodlewsrestformat' => 'json',
            'courseid' => $curso['id'],
        ]);

        $usuarios = collect($enrolledResponse->json());

        $estudiantes = $usuarios->filter(function ($u) {
            return collect($u['roles'])->pluck('roleid')->contains(5);
        });

        if ($estudiantes->isEmpty()) {
            return back()->withErrors(['error' => 'No hay estudiantes matriculados en este curso.']);
        }

        // Obtener las notas con escala 10 y estado
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

                if (
                    isset($total['graderaw']) &&
                    isset($total['grademax']) &&
                    floatval($total['grademax']) > 0
                ) {
                    $promedio = round(($total['graderaw'] / $total['grademax']) * 10, 2);
                }
            }

            return [
                'fullname' => $estudiante['fullname'],
                'email' => $estudiante['email'],
                'promedio' => number_format($promedio, 2),
                'estado' => $promedio >= 7 ? 'Aprobado' : 'Reprobado',
            ];
        });

        $fecha = now()->format('d-m-Y');

        $pdf = Pdf::loadView('profesor.reporte.pdf', [
            'curso' => $curso['fullname'],
            'shortname' => $curso['shortname'],
            'estudiantes' => $estudiantesConNotas,
            'fecha' => $fecha,
        ]);

        return $pdf->download("reporte_notas_{$curso['shortname']}.pdf");
    }


}
