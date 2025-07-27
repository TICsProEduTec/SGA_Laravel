<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisualizacionController extends Controller
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

        $cursos = collect($response->json())
            ->filter(fn($curso) => isset($curso['id'], $curso['fullname']))
            ->values();

        return view('profesor.visualizacion.index', compact('cursos'));
    }

    public function datosCurso($cursoId)
    {
        try {
            Log::info("📥 [datosCurso] Curso recibido: $cursoId");

            $token = config('services.moodle.token');
            $endpoint = config('services.moodle.endpoint');

            // Obtener usuarios matriculados en el curso
            $urlUsers = $endpoint . '?wstoken=' . $token .
                '&wsfunction=core_enrol_get_enrolled_users' .
                '&moodlewsrestformat=json' .
                '&courseid=' . $cursoId;

            $responseUsers = Http::get($urlUsers);
            $enrolledUsers = $responseUsers->json();

            // Filtrar solo estudiantes (roleid = 5)
            $estudiantes = collect($enrolledUsers)->filter(function ($user) {
                foreach ($user['roles'] as $role) {
                    if ($role['roleid'] == 5) return true;
                }
                return false;
            });

            $aprobados = 0;
            $reprobados = 0;
            $total = 0;
            $contador = 0;

            foreach ($estudiantes as $user) {
                $userId = $user['id'];

                $url = $endpoint . '?wstoken=' . $token .
                    '&wsfunction=gradereport_user_get_grade_items' .
                    '&moodlewsrestformat=json' .
                    '&courseid=' . $cursoId .
                    '&userid=' . $userId;

                $response = Http::get($url);
                $data = $response->json();

                if (!empty($data['usergrades'][0]['gradeitems'])) {
                    $sumaNotas = 0;
                    $cuentaNotas = 0;

                    foreach ($data['usergrades'][0]['gradeitems'] as $item) {
                        if (
                            $item['itemtype'] === 'mod' &&
                            isset($item['graderaw']) &&
                            isset($item['grademax']) &&
                            floatval($item['grademax']) > 0
                        ) {
                            $raw = floatval($item['graderaw']);
                            $max = floatval($item['grademax']);
                            $normalizado = ($raw / $max) * 10; // Escala 10

                            $sumaNotas += $normalizado;
                            $cuentaNotas++;
                        }
                    }

                    if ($cuentaNotas > 0) {
                        $notaFinal = round($sumaNotas / $cuentaNotas, 2);

                        Log::info("👤 {$user['fullname']} → Promedio escalado: $notaFinal");

                        $contador++;
                        $total += $notaFinal;

                        if ($notaFinal >= 7) {
                            $aprobados++;
                        } else {
                            $reprobados++;
                        }
                    }
                }
            }

            $promedio = $contador > 0 ? round($total / $contador, 2) : 0;

            Log::info("✅ Total evaluados: $contador | Aprobados: $aprobados | Reprobados: $reprobados | Promedio: $promedio");

            return response()->json([
                'aprobados' => $aprobados,
                'reprobados' => $reprobados,
                'promedio' => $promedio
            ]);
        } catch (\Exception $e) {
            Log::error("❌ Error en datosCurso: " . $e->getMessage());
            return response()->json(['error' => 'Error al obtener datos del curso.'], 500);
        }
    }


    public function tareasCurso($cursoId)
    {
        $response = Http::get(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_course_get_contents',
            'moodlewsrestformat' => 'json',
            'courseid' => $cursoId
        ]);

        $secciones = $response->json();
        $tareas = [];

        foreach ($secciones as $seccion) {
            if (isset($seccion['modules'])) {
                foreach ($seccion['modules'] as $mod) {
                    if ($mod['modname'] === 'assign') {
                        $tareas[] = [
                            'nombre' => $mod['name'],
                            'visible' => $mod['visible'] ? 'Activa' : 'Oculta',
                        ];
                    }
                }
            }
        }

        return response()->json($tareas);
    }
}
