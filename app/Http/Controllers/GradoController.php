<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Grado;
use App\Models\Periodo;
use App\Models\Plantilla;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mpdf\Mpdf;

class GradoController extends Controller
{
    private $token;
    private $endpoint;

    public function __construct()
    {
        $this->token = config('services.moodle.token');
        $this->endpoint = config('services.moodle.endpoint');
    }

    public function index() {}
    public function create() {}

    public function creargrado(Periodo $periodo)
    {
        $plantillas = Plantilla::all();
        return view('grados.create', compact('plantillas', 'periodo'));
    }

    public function store(Request $request) {}

    public function store2(Request $request, Periodo $periodo)
    {
        $request->validate([
            'name' => 'required',
            'plantilla_id' => 'required',
        ]);

        $plantilla = Plantilla::with('cursos')->find($request->input('plantilla_id'));

        $consulta = $this->endpoint
            . '?wstoken=' . $this->token
            . '&wsfunction=core_course_create_categories'
            . '&moodlewsrestformat=json'
            . '&categories[0][name]=' . urlencode($request->input('name'))
            . '&categories[0][parent]=' . $periodo->id_category_moodle
            . '&categories[0][descriptionformat]=0';

        $response = Http::get($consulta);
        $data = json_decode($response);

        if (!is_array($data) || !isset($data[0])) {
            Log::info('Error al crear categoría en Moodle', ['response' => $response->body()]);
            return back()->with('info', 'Error al crear la categoría en Moodle.');
        }

        $cd_categoria = $data[0];

        $grado = new Grado();
        $grado->name = $request->input('name');
        $grado->periodo_id = $periodo->id;
        $grado->id_category_moodle = $cd_categoria->id;
        $grado->save();

        foreach ($plantilla->cursos as $curso) {
            $shortname = strtoupper(str_replace([' ', '/', '-'], '_', $periodo->name . ' ' . $grado->name . ' ' . $curso->name));

            $c_curso = Http::get($this->endpoint, [
                'wstoken' => $this->token,
                'wsfunction' => 'core_course_create_courses',
                'moodlewsrestformat' => 'json',
                'courses[0][fullname]' => $curso->name,
                'courses[0][shortname]' => $shortname,
                'courses[0][categoryid]' => $cd_categoria->id,
            ]);

            $data_curso = json_decode($c_curso);

            if (!is_array($data_curso) || !isset($data_curso[0])) {
                Log::info('Error al crear curso en Moodle', ['response' => $c_curso->body()]);
                continue;
            }

            $cd_curso = $data_curso[0];

            try {
                Area::create([
                    'name' => $curso->name,
                    'shortname' => $shortname,
                    'id_course_moodle' => $cd_curso->id,
                    'grado_id' => $grado->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Error al guardar área en la BD: ' . $e->getMessage());
            }

            try {
                \App\Models\Curso::create([
                    'name' => $curso->name,
                    'plantilla_id' => $curso->pivot->plantilla_id ?? $plantilla->id,
                    'Grados_id' => $grado->id,
                    'id_curso_moodle' => $cd_curso->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Error al guardar curso real en la tabla cursos: ' . $e->getMessage());
            }
        }

        return redirect()->route('periodos.index')
            ->with('info', 'El grado y sus cursos fueron creados correctamente en Moodle.');
    }

    public function consultarmatricula(Grado $grado)
    {
        $matriculados = $grado->users->pluck('id');

        // Normalizar nombres
        $gradoNombre = strtolower(str_replace(' ', '_', $grado->name));
        $periodoNombre = strtolower(str_replace(' ', '_', $grado->periodo->name));

        // Obtener IDs Moodle de usuarios que ya son docentes en algún curso del grado
        $docentesAsignados = collect($grado->areas)->flatMap(function ($area) {
            $response = Http::asForm()->post($this->endpoint, [
                'wstoken' => $this->token,
                'wsfunction' => 'core_enrol_get_enrolled_users',
                'moodlewsrestformat' => 'json',
                'courseid' => $area->id_course_moodle,
            ]);

            $data = $response->json();

            return collect($data)->filter(function ($user) {
                return collect($user['roles'])->contains('roleid', 3); // Solo docentes
            })->pluck('id'); // ID de Moodle
        })->unique();

        // Buscar usuarios en Laravel que no estén matriculados ni sean docentes
        $users = User::whereNotIn('id', $matriculados)
            ->whereRaw('LOWER(REPLACE(grado, " ", "_")) = ?', [$gradoNombre])
            ->whereRaw('LOWER(REPLACE(periodo, " ", "_")) = ?', [$periodoNombre])
            ->whereNotIn('id_user_moodle', $docentesAsignados)
            ->get();

        return view('grados.consultarmatricula', compact('grado', 'users'));
    }

    public function matricular(Request $request, Grado $grado)
    {
        $userIds = $request->input('user_ids') ?? [$request->input('user_id')];
        $asignarDocente = $request->input('asignar_docente') == '1'; // Botón presionado
        $rol = $asignarDocente ? 3 : 5; // 3 = docente, 5 = estudiante

        if (empty($userIds)) {
            return back()->with('info', 'No se seleccionó ningún usuario.');
        }

        foreach ($userIds as $id) {
            $user = User::find($id);

            if (!$user || !$user->id_user_moodle) {
                Log::warning("Usuario inválido o sin ID Moodle: ID {$id}");
                continue;
            }

            // Asociar al grado localmente
            $grado->users()->syncWithoutDetaching([$user->id]);

            // ✅ ACTUALIZAR EL CAMPO 'rol' EN LARAVEL
            $user->rol = $asignarDocente ? 'profesor' : 'estudiante';
            $user->save();

            // Matricular en Moodle en todas las áreas del grado
            foreach ($grado->areas as $area) {
                $shortname = $area->shortname;

                $getCourseId = Http::asForm()->post($this->endpoint, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'core_course_get_courses_by_field',
                    'moodlewsrestformat' => 'json',
                    'field' => 'shortname',
                    'value' => $shortname,
                ]);

                $data = $getCourseId->json();
                $courseId = $data['courses'][0]['id'] ?? null;

                if (!$courseId) {
                    Log::info("No se encontró el curso con shortname: $shortname");
                    continue;
                }

                $response = Http::asForm()->post($this->endpoint, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'enrol_manual_enrol_users',
                    'moodlewsrestformat' => 'json',
                    'enrolments[0][roleid]' => $rol,
                    'enrolments[0][userid]' => $user->id_user_moodle,
                    'enrolments[0][courseid]' => $courseId,
                ]);

                $data = $response->json();

                if (isset($data['exception'])) {
                    Log::info('Error al matricular en Moodle', ['response' => $data]);
                    continue;
                }
            }
        }

        $mensaje = $asignarDocente ? 'Profesor(s) asignado(s) correctamente.' : 'Estudiante(s) matriculado(s) correctamente.';
        return redirect()->route('grado.consultarmatricula', $grado->id)->with('info', $mensaje);
    }

    public function desmatricular(Request $request, Grado $grado, User $user = null)
    {
        $usuarios = collect();

        // Si es llamado desde checkbox múltiples (POST masivo)
        if ($request->has('user_ids')) {
            $usuarios = User::whereIn('id', $request->input('user_ids'))->get();
        }
        // Si viene desde botón individual
        elseif ($user) {
            $usuarios->push($user);
        }

        foreach ($usuarios as $usuario) {
            $usuario->grados()->detach($grado->id);

            foreach ($grado->areas as $area) {
                $shortname = $area->shortname;

                // Obtener el ID del curso Moodle por shortname
                $getCourseId = Http::asForm()->post($this->endpoint, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'core_course_get_courses_by_field',
                    'moodlewsrestformat' => 'json',
                    'field' => 'shortname',
                    'value' => $shortname,
                ]);

                $data = $getCourseId->json();
                $courseId = $data['courses'][0]['id'] ?? null;

                if (!$courseId) {
                    Log::info("No se encontró el curso con shortname: $shortname");
                    continue;
                }

                // Llamada a unenrol
                $response = Http::asForm()->post($this->endpoint, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'enrol_manual_unenrol_users',
                    'moodlewsrestformat' => 'json',
                    'enrolments[0][userid]' => $usuario->id_user_moodle,
                    'enrolments[0][courseid]' => $courseId,
                ]);

                $resData = $response->json();

                if (isset($resData['exception'])) {
                    Log::error('Error al desmatricular en Moodle', ['response' => $resData]);
                    return back()->with('info', 'Error al desmatricular en Moodle: ' . $resData['message']);
                }
            }
        }

        return redirect()->route('grado.consultarmatricula', $grado->id)
            ->with('info', 'Estudiante(s) desmatriculado(s) correctamente.');
    }

    public function consultarnotas(Grado $grado, User $user)
    {
        $response = Http::get($this->endpoint, [
            'wstoken' => $this->token,
            'wsfunction' => 'gradereport_overview_get_course_grades',
            'moodlewsrestformat' => 'json',
            'userid' => $user->id_user_moodle,
        ]);

        $data = json_decode($response);
        $l_notas = [];

        foreach ($grado->areas as $area) {
            $l_notas[$area->id] = [
                'nombre' => $area->name,
                'nota' => null,
            ];
        }

        if (isset($data->grades) && is_array($data->grades)) {
            foreach ($data->grades as $nota) {
                foreach ($grado->areas as $area) {
                    if ($area->id_course_moodle == $nota->courseid) {
                        $l_notas[$area->id]['nota'] = $nota->grade;
                    }
                }
            }
        }

        return view('grados.consultarnotas', compact('grado', 'user', 'l_notas'));
    }
        /**
     * Mostrar detalle de calificaciones de una materia (área)
     */
    public function verDetalleNota(Grado $grado, User $user, $areaId)
    {
        $area = $grado->areas->find($areaId);

        $resp = Http::asForm()->post($this->endpoint, [
            'wstoken'            => $this->token,
            'wsfunction'         => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid'           => (int)$area->id_course_moodle,
            'userid'             => (int)$user->id_user_moodle,
        ]);

        $data = $resp->json();
        $allItems = $data['usergrades'][0]['gradeitems'] ?? [];

        // Filtramos solo actividades reales (tienen itemmodule) y/o cuyo nombre incluya "leccion"
        $gradeitems = array_filter($allItems, function ($item) {
            $hasModule = !empty($item['itemmodule']);
            $isLeccion = Str::contains(Str::lower($item['itemname']), 'leccion');
            $hasGrade  = (!empty($item['gradeformatted']) || (isset($item['grade']) && is_numeric($item['grade'])));
            return $hasGrade && ($hasModule || $isLeccion);
        });

        return view('grados.detallenota', compact('grado', 'user', 'area', 'gradeitems'));
    }


    /**
     * Formulario para editar manualmente la nota de una materia
     */
    public function editarNota(Grado $grado, User $user, $areaId)
    {
        // Recuperamos el área
        $area = $grado->areas->find($areaId);

        // Llamada a Moodle para traer solo la nota de esta materia
        $resp = Http::asForm()->post($this->endpoint, [
            'wstoken'            => $this->token,
            'wsfunction'         => 'gradereport_overview_get_course_grades',
            'moodlewsrestformat' => 'json',
            'userids[0]'         => $user->id_user_moodle,
            'courseids[0]'       => $area->id_course_moodle,
        ]);

        $data = $resp->json();

        // Extraemos la nota formateada o el valor bruto
        $nota = null;
        if (!empty($data['grades'][0])) {
            $nota = $data['grades'][0]['gradeformatted']
                ?? $data['grades'][0]['grade']
                ?? null;
        }

        // Pasamos la nota actual a la vista
        return view('grados.editarnota', compact('grado', 'user', 'area', 'nota'));
    }
  /**
     * Procesa el formulario y actualiza la nota en Moodle
     */
    public function updateNota(Request $request, Grado $grado, User $user, $areaId)
    {
        $request->validate([
            'nota' => 'required|numeric|between:0,100'
        ]);
        $newGrade = $request->input('nota');

        // 1) Recuperar el área y sus IDs
        $area     = $grado->areas->findOrFail($areaId);
        $courseId = (int) $area->id_course_moodle;
        $userId   = (int) $user->id_user_moodle;

        // 2) Traer todos los gradeitems de esa materia
        $itemsData = Http::asForm()->post($this->endpoint, [
            'wstoken'            => $this->token,
            'wsfunction'         => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid'           => $courseId,
            'userid'             => $userId,
        ])->json();
        $gradeitems = $itemsData['usergrades'][0]['gradeitems'] ?? [];

        // 3) Buscar el ítem de la lección por módulo "lesson"
        $lessonItem = collect($gradeitems)
            ->first(fn($it) => isset($it['itemmodule']) && $it['itemmodule'] === 'lesson');

        if (! $lessonItem || empty($lessonItem['itemid'])) {
            return back()->with('info', 'No pude encontrar el ítem de la Lección en Moodle.');
        }
        $itemId = $lessonItem['itemid'];

        // 4) Enviar solo esa actividad a core_grades_update_grades
        $result = Http::asForm()->post($this->endpoint, [
            'wstoken'            => $this->token,
            'wsfunction'         => 'core_grades_update_grades',
            'moodlewsrestformat' => 'json',
            'gradeitems[0][id]'                      => $itemId,
            'gradeitems[0][grades][0][userid]'       => $userId,
            'gradeitems[0][grades][0][rawgrade]'     => $newGrade,
            'gradeitems[0][grades][0][feedback]'     => '',
            'gradeitems[0][grades][0][usermodified]' => $userId,
        ])->json();

        if (isset($result['exception'])) {
            return back()->with('info', "Error actualizando la Lección: {$result['message']}");
        }

        return redirect()
            ->route('grado.verDetalleNota', [
                'grado' => $grado->id,
                'user'  => $user->id,
                'area'  => $areaId,
            ])
            ->with('info', 'La nota de la Lección se actualizó correctamente en Moodle.');
    }


    public function generarPDF(Grado $grado, User $user)
    {
        $response = Http::get($this->endpoint, [
            'wstoken' => $this->token,
            'wsfunction' => 'gradereport_overview_get_course_grades',
            'moodlewsrestformat' => 'json',
            'userid' => $user->id_user_moodle,
        ]);

        $data = json_decode($response);
        $l_notas = [];

        foreach ($grado->areas as $area) {
            $l_notas[$area->id] = [
                'nombre' => $area->name,
                'nota' => null,
            ];
        }

        if (isset($data->grades) && is_array($data->grades)) {
            foreach ($data->grades as $nota) {
                foreach ($grado->areas as $area) {
                    if ($area->id_course_moodle == $nota->courseid) {
                        $l_notas[$area->id]['nota'] = $nota->grade;
                    }
                }
            }
        }

        // Instanciamos mPDF
        $mpdf = new Mpdf();

        // Cargamos el HTML de la vista
        $html = view('grados.notaspdf', compact('grado', 'user', 'l_notas'))->render();

        // Escribimos el HTML en el archivo PDF
        $mpdf->WriteHTML($html);

        // Devolvemos el PDF al navegador
        return $mpdf->Output('notas_' . $user->name . '.pdf', 'I');
    }

    public function destroy($id)
    {
        $grado = Grado::with('cursos')->findOrFail($id);

        // Eliminar cursos de Moodle asociados al grado
        foreach ($grado->cursos as $curso) {
            if ($curso->id_curso_moodle) {
                try {
                    Http::asForm()->post($this->endpoint, [
                        'wstoken' => $this->token,
                        'wsfunction' => 'core_course_delete_courses',
                        'moodlewsrestformat' => 'json',
                        'courseids[0]' => $curso->id_curso_moodle,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error al eliminar curso en Moodle: " . $e->getMessage());
                }
            }
        }

        // Elimina cursos de Laravel
        $grado->cursos()->delete();

        // Elimina áreas (por si no se eliminan en cascada)
        $grado->areas()->delete();

        // Elimina la categoría del grado en Moodle
        if ($grado->id_category_moodle) {
            try {
                Http::asForm()->post($this->endpoint, [
                    'wstoken' => $this->token,
                    'wsfunction' => 'core_course_delete_categories',
                    'moodlewsrestformat' => 'json',
                    'categories[0][id]' => $grado->id_category_moodle,
                    'categories[0][newparent]' => 0,
                    'categories[0][recursive]' => 1,
                ]);
            } catch (\Exception $e) {
                Log::error("Error al eliminar categoría en Moodle: " . $e->getMessage());
            }
        }

        // Elimina grado
        $grado->delete();

        return back()->with('info', 'El grado y sus cursos fueron eliminados correctamente de Laravel y Moodle.');
    }

}
