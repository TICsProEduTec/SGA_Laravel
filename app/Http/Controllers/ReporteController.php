<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


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
            // Buscar usuario local por id_user_moodle
            $user = User::where('id_user_moodle', $userId)->firstOrFail();

            // Buscar curso local por id_curso_moodle
            $curso = Curso::where('id_curso_moodle', $cursoId)->firstOrFail();

            // Llamada al webservice para obtener calificaciones
            $resp = Http::asForm()->post(config('services.moodle.endpoint'), [
                'wstoken'            => config('services.moodle.token'),
                'wsfunction'         => 'gradereport_user_get_grade_items',
                'moodlewsrestformat' => 'json',
                'courseid'           => (int)$curso->id_curso_moodle,
                'userid'             => (int)$user->id_user_moodle,
            ]);

            $data = $resp->json();
            $allItems = $data['usergrades'][0]['gradeitems'] ?? [];

            // Filtrar solo ítems calificados relevantes
            $tareas = array_filter($allItems, function($item) {
                $hasModule = !empty($item['itemmodule']);
                $isLeccion = Str::contains(Str::lower($item['itemname']), 'leccion');
                $hasGrade  = (!empty($item['gradeformatted']) || (isset($item['grade']) && is_numeric($item['grade'])));
                return $hasGrade && ($hasModule || $isLeccion);
            });

            return view('profesor.reporte.tareas', compact('user', 'curso', 'tareas'));

        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }
    }



}
