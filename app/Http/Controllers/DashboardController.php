<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Curso;
use App\Models\User;
use App\Models\Feedback;
use App\Models\Grado;
use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Services\MoodleGradeService;
use App\Services\MoodleModuleService; // ✅ nuevo
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    protected MoodleGradeService $gradeService;
    protected OpenAIService $aiService;
    protected MoodleModuleService $moduleService; // ✅ nuevo

    public function __construct(
        MoodleGradeService $gradeService,
        OpenAIService $aiService,
        MoodleModuleService $moduleService // ✅ nuevo
    ) {
        $this->gradeService = $gradeService;
        $this->aiService = $aiService;
        $this->moduleService = $moduleService;
    }

    public function index()
    {
        $grados = Grado::all();
        return view('dashboard.docentes_grados', compact('grados'));
    }

    public function show(int $courseId)
    {
        set_time_limit(120);
        Log::info("▶️ Iniciando análisis de curso ID (Moodle): {$courseId}");

        $localCurso = Curso::with('area')->where('id_curso_moodle', $courseId)->first();
        if (!$localCurso) {
            Log::error("❌ Curso local no encontrado con id_curso_moodle = {$courseId}");
            return view('dashboard.docentes_show', [
                'courseId' => $courseId,
                'students' => [],
                'feedbacks' => [],
            ]);
        }

        // Usamos las variables del archivo .env para el token y la URL
        $response = Http::asForm()->timeout(60)->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId,
        ]);

        $data = $response->json();
        if (!isset($data['usergrades']) || !is_array($data['usergrades'])) {
            Log::warning("⚠️ No se encontraron calificaciones.");
            return view('dashboard.docentes_show', [
                'courseId' => $courseId,
                'students' => [],
                'feedbacks' => [],
            ]);
        }

        $resultados = [];
        $feedbacks = [];

        foreach ($data['usergrades'] as $user) {
            $notaFinal = null;
            if (!isset($user['gradeitems'])) continue;

            foreach ($user['gradeitems'] as $item) {
                if ($item['itemtype'] === 'course') {
                    $notaFinal = $item['graderaw'] ?? null;
                    break;
                }
            }

            if ($notaFinal === null) continue;

            $estado = $notaFinal < 7 ? 'Debe ir a recuperación' : 'Aprobado';
            $localUser = User::where('id_user_moodle', $user['userid'])->first();
            $user_id_local = $localUser?->id;

            $estudiante = [
                'userid' => $user['userid'],
                'fullname' => $user['userfullname'] ?? 'Sin nombre',
                'promedio' => floatval($notaFinal),
                'estado' => $estado,
                'user_id_local' => $user_id_local,
            ];
            $resultados[] = $estudiante;

            if ($estado === 'Debe ir a recuperación' && $localUser) {
                $feedback = Feedback::where('curso_id', $localCurso->id)
                                     ->where('user_id', $localUser->id)
                                     ->first();
                if ($feedback) {
                    $feedbacks[$localUser->id] = $feedback->contenido;
                }
            }
        }

        Log::info("📤 Se procesaron " . count($resultados) . " estudiantes.");
        return view('dashboard.docentes_show', [
            'courseId' => $courseId,
            'students' => $resultados,
            'feedbacks' => $feedbacks,
        ]);
    }

    public function updateFeedback(Request $request, $courseId, $userId)
    {
        $request->validate(['contenido' => 'required|string']);

        $curso = Curso::where('id_curso_moodle', $courseId)->first();
        if (!$curso) {
            return redirect()->back()->with('error', 'Curso no encontrado.');
        }

        Feedback::updateOrCreate(
            ['curso_id' => $curso->id, 'user_id' => $userId],
            ['contenido' => $request->contenido, 'generado_por' => 'Docente', 'fecha_generado' => now()]
        );

        return redirect()->back()->with('success', 'Feedback actualizado correctamente.');
    }

    public function materias($gradoId)
    {
        $grado = Grado::find($gradoId);
        if (!$grado) {
            return redirect()->route('dashboard.docentes.index')->with('error', 'Grado no encontrado');
        }

        $materias = Area::where('grado_id', $gradoId)->get();
        return view('dashboard.docentes_materias', compact('grado', 'materias'));
    }

    public function chatFeedback(Request $request, $cursoId, $userId)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        try {
            $curso = Curso::with('area')->where('id_curso_moodle', $cursoId)->firstOrFail();
            $user = User::findOrFail($userId);
            $mensajeUsuario = $request->message;

            $feedback = Feedback::firstOrNew([
                'curso_id' => $curso->id,
                'user_id'  => $user->id,
            ]);

            $historial = $feedback->contenido ?? '';
            $prompt = "Historial del chat entre docente e IA:\n" . $historial . 
                    "\n👤 {$user->name}: {$mensajeUsuario}\n🤖";

            $respuestaIA = $this->aiService->generarTexto($prompt);
            $respuestaTexto = $respuestaIA['output'] ?? 'Sin respuesta de IA.';
            $historial .= "\n👤 {$user->name}: {$mensajeUsuario}\n🤖 IA: {$respuestaTexto}";

            $feedback->contenido = $historial;
            $feedback->generado_por = 'IA';
            $feedback->fecha_generado = now();
            $feedback->save();

            // 👉 CREAR TAREA AUTOMÁTICA EN MOODLE
            $taskPlantillaId = 384;
            $nombre = "Actividad de recuperación para {$user->name}";
            $descripcion = $respuestaTexto;

            $params = [
                'wstoken' => config('services.moodle.token'),
                'wsfunction' => 'core_course_duplicate_module',
                'moodlewsrestformat' => 'json',
                'moduleid' => $taskPlantillaId,
                'courseid' => $cursoId,
                'fullname' => $nombre,
                'options[0][name]' => 'section',  // si sabes la sección puedes ponerla aquí
                'options[1][name]' => 'visible',
                'options[1][value]' => 1,
            ];

            $response = Http::asForm()->post(config('services.moodle.endpoint'), $params);
            $res = $response->json();

            if (isset($res['cmid'])) {
                $cmid = $res['cmid'];

                // editar la nueva tarea con la descripción generada
                $updateParams = [
                    'wstoken' => config('services.moodle.token'),
                    'wsfunction' => 'core_course_edit_module',
                    'moodlewsrestformat' => 'json',
                    'cmid' => $cmid,
                    'options[0][name]' => 'name',
                    'options[0][value]' => $nombre,
                    'options[1][name]' => 'description',
                    'options[1][value]' => $descripcion,
                    'options[2][name]' => 'descriptionformat',
                    'options[2][value]' => 1,
                    'options[3][name]' => 'duedate',
                    'options[3][value]' => now()->addDays(7)->timestamp,
                ];

                Http::asForm()->post(config('services.moodle.endpoint'), $updateParams);
            } else {
                Log::warning("⚠️ No se pudo duplicar la tarea en Moodle.", ['response' => $res]);
            }

            return response()->json(['respuesta' => $respuestaTexto]);

        } catch (\Exception $e) {
            Log::error('💥 Error general en chatFeedback:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Error al contactar IA.'], 500);
        }
    }
    
}
