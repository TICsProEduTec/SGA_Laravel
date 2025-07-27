<?php

namespace App\Http\Controllers;

use App\Models\Contenido;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Services\OpenAIService;
use App\Services\MoodleGradeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Session;

class RetroalimentacionAIController extends Controller
{
    protected OpenAIService $iaService;
    protected MoodleGradeService $gradeService;

    public function __construct(OpenAIService $iaService, MoodleGradeService $gradeService)
    {
        $this->iaService = $iaService;
        $this->gradeService = $gradeService;
    }

    public function viewDocente()
    {
        return view('profesor.ia.index');
    }

    public function procesarDocente(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'archivo' => 'nullable|file|mimes:pdf|max:10240'
        ]);

        try {
            $docente = Auth::user();
            $mensaje = strtolower($request->input('message', ''));
            $mensajeNormalizado = Str::lower(Str::ascii($mensaje));
            // 🟢 Sugerencias reales si el mensaje es "hola"
            if (in_array($mensaje, ['hola', 'hola!', 'buenos días', 'buenas', 'hi', 'Hi'])) {
                return response()->json([
                    'respuesta' => "👋 ¡Hola {$docente->name}! Soy tu asistente académico. Aquí tienes algunas sugerencias que puedes probar:",
                    'sugerencias' => [
                        "📄 Analiza el PDF que subí",                      // Retroalimentación por curso
                        "✏️ retroalimentación para Juan en Física",            // Retro específica por estudiante y materia
                        "📊 Estudiantes reprobados en Matemáticas",            // Reprobados por materia
                        "📁 Ver retroalimentación del PDF subido",             // Feedback desde archivo PDF
                        "📋 Dame el listado de estudiantes reprobados"
                    ]
                ]);
            }


            $userIdMoodle = $docente->id_user_moodle;

            if (!$userIdMoodle) {
                return response()->json([
                    'respuesta' => '❌ El docente no tiene asignado un ID de Moodle. Por favor, contacta con el administrador.'
                ], 400);
            }

            $contenidoPDF = '';

            // Si se subió un nuevo archivo PDF en esta petición
            if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($request->file('archivo')->getPathname());
                    $contenidoPDF = $pdf->getText();

                    // Guardar en sesión
                    Session::put('texto_pdf', $contenidoPDF);

                    Log::info("✅ PDF procesado correctamente y guardado en sesión.");
                } catch (\Throwable $e) {
                    Log::error("❌ Error al leer el PDF: " . $e->getMessage());
                    return response()->json([
                        'respuesta' => 'Error al procesar el archivo PDF.',
                    ], 500);
                }
            } elseif (Session::has('texto_pdf')) {
                $contenidoPDF = Session::get('texto_pdf');
                Log::info("📂 PDF recuperado desde sesión.");
            }


            $prefijo = '';
            if ($contenidoPDF && $mensaje) {
                $prefijo = "El docente escribió lo siguiente: {$mensaje}\n\nAdemás, analiza el siguiente documento PDF:\n\n{$contenidoPDF}";
            } elseif ($contenidoPDF) {
                $prefijo = "Analiza el siguiente documento PDF:\n\n{$contenidoPDF}";
            } elseif ($mensaje) {
                $prefijo = "El docente pregunta: {$mensaje}";
            } else {
                return response()->json(['respuesta' => '❌ Debe ingresar un mensaje o subir un archivo PDF.'], 422);
            }

            // 🧠 Retroalimentación completa por curso
            if (Str::contains($mensaje, 'retroalimentación completa')) {
                $cursos = $this->obtenerCursosDelDocenteDesdeMoodle((int)$userIdMoodle);
                $listadoReprobados = [];

                foreach ($cursos as $curso) {
                    $notas = $this->gradeService->getFinalGradesFromCourse($curso['id']);

                    foreach ($notas as $nota) {
                        if (!isset($nota['finalgrade']) || $nota['finalgrade'] >= 7) continue;

                        $prompt = "Contenido del curso:\n\n{$contenidoPDF}\n\n"
                            . "El estudiante {$nota['user_fullname']} obtuvo {$nota['finalgrade']} en {$curso['fullname']}.\n"
                            . "Genera retroalimentación personalizada, una lección de recuperación, y una mini actividad evaluativa.";

                        $respuestaIA = $this->iaService->generarTexto($prompt);

                        $listadoReprobados[] = [
                            'nombre' => $nota['user_fullname'],
                            'curso' => $curso['fullname'],
                            'nota' => round($nota['finalgrade'], 2),
                            'retro' => $respuestaIA['output'] ?? 'Sin respuesta IA.',
                            'actividad' => 'Actividad propuesta incluida en la respuesta.'
                        ];
                    }
                }

                $contenido = view('profesor.ia.reporte_pdf_individual', ['estudiantes' => $listadoReprobados])->render();
                $pdf = Pdf::loadHTML($contenido);
                $filename = 'retroalimentacion_completa_' . Str::slug(now()) . '.pdf';

                Storage::disk('public')->put('ia_docs/' . $filename, $pdf->output());
                $urlPdf = asset('storage/ia_docs/' . $filename);

                return response()->json([
                    'respuesta' => '📘 Se generó retroalimentación completa para estudiantes reprobados.',
                    'pdf' => $urlPdf
                ]);
            }

            // 🔁 Retroalimentación básica
            if (Str::contains($mensaje, 'retroalimentación para')) {
                $respuestaIA = $this->iaService->generarTexto("Genera una retroalimentación académica personalizada para un estudiante que reprobó. {$prefijo}");
                $contenido = $respuestaIA['output'] ?? 'No se pudo generar retroalimentación.';

                $filenameTxt = 'retroalimentacion_' . Str::slug(now()) . '.txt';
                Storage::disk('public')->put('ia_docs/' . $filenameTxt, $contenido);
                $urlTxt = asset('storage/ia_docs/' . $filenameTxt);

                $pdf = Pdf::loadView('profesor.ia.feedback', compact('contenido'));
                $filenamePdf = 'retroalimentacion_' . Str::slug(now()) . '.pdf';
                Storage::disk('public')->put('ia_docs/' . $filenamePdf, $pdf->output());
                $urlPdf = asset('storage/ia_docs/' . $filenamePdf);

                return response()->json([
                    'respuesta' => $contenido,
                    'archivo' => $urlTxt,
                    'pdf' => $urlPdf
                ]);
            }

            // ✅ Resumen de notas promediadas (escaladas a 10) con detección robusta de mensaje
            $mensajeNormalizado = Str::lower(Str::ascii($mensaje));
            if (
                Str::contains($mensajeNormalizado, 'reprobado') ||
                Str::contains($mensajeNormalizado, 'aprobado') ||
                Str::contains($mensajeNormalizado, 'quienes aprobaron y reprobaron') ||
                Str::contains($mensajeNormalizado, 'listado de estudiantes reprobados') ||
                Str::contains($mensajeNormalizado, 'ver reprobados') ||
                Str::contains($mensajeNormalizado, 'mostrar reprobados')
            ) {
                $cursos = $this->obtenerCursosDelDocenteDesdeMoodle((int)$userIdMoodle);
                $listadoReprobados = [];
                $listadoAprobados = [];

                // Detección de materias
                $materiasDetectadas = ['fisica', 'matematicas', 'lengua', 'lenguaje', 'literatura', 'quimica', 'biologia', 'ingles', 'lenguaj'];
                $materiaFiltrada = null;
                foreach ($materiasDetectadas as $materia) {
                    if (Str::contains($mensajeNormalizado, $materia)) {
                        $materiaFiltrada = $materia;
                        break;
                    }
                }

                foreach ($cursos as $curso) {
                    $nombreCursoNormalizado = Str::lower(Str::ascii($curso['fullname']));

                    if ($materiaFiltrada && !Str::contains($nombreCursoNormalizado, $materiaFiltrada)) {
                        continue;
                    }

                    // ✅ Usamos las notas promediadas
                    $notas = $this->gradeService->getCourseGradesWithAverages($curso['id']);

                    foreach ($notas as $nota) {
                        if (!isset($nota['average'])) continue;

                        // Escalar nota sobre 10 si viene sobre 100
                        $nota10 = $nota['average'] > 10 ? round($nota['average'] / 10, 2) : round($nota['average'], 2);

                        $item = [
                            'nombre' => $nota['user_fullname'],
                            'curso' => $curso['fullname'],
                            'nota' => $nota10,
                        ];

                        if ($nota10 < 7) {
                            $listadoReprobados[] = $item;
                        } else {
                            $listadoAprobados[] = $item;
                        }
                    }
                }

                // ✅ Construcción del mensaje de respuesta
                $respuesta = "📋 **Resumen de notas en tus cursos:**\n\n";

                $soloReprobados = Str::contains($mensajeNormalizado, [
                    'solo reprobados',
                    'ver reprobados',
                    'quienes reprobaron',
                    'estudiantes reprobados',
                    'dame el listado de estudiantes reprobados',
                    'listado de estudiantes reprobados',
                    'mostrar reprobados'
                ]) && !Str::contains($mensajeNormalizado, 'aprobado');

                if ($soloReprobados) {
                    if (count($listadoReprobados) > 0) {
                        $respuesta .= "❌ Estudiantes reprobados:\n";
                        foreach ($listadoReprobados as $est) {
                            $respuesta .= "- {$est['nombre']} ({$est['curso']}): Nota {$est['nota']}\n";
                        }
                    } else {
                        $respuesta .= "✔️ No se encontraron estudiantes reprobados.";
                    }
                } else {
                    if (count($listadoAprobados) > 0) {
                        $respuesta .= "✅ Estudiantes aprobados:\n";
                        foreach ($listadoAprobados as $est) {
                            $respuesta .= "- {$est['nombre']} ({$est['curso']}): Nota {$est['nota']}\n";
                        }
                        $respuesta .= "\n";
                    }

                    if (count($listadoReprobados) > 0) {
                        $respuesta .= "❌ Estudiantes reprobados:\n";
                        foreach ($listadoReprobados as $est) {
                            $respuesta .= "- {$est['nombre']} ({$est['curso']}): Nota {$est['nota']}\n";
                        }
                    }

                    if (count($listadoAprobados) === 0 && count($listadoReprobados) === 0) {
                        $respuesta .= "⚠️ No se encontraron estudiantes con notas en los cursos analizados.";
                    }
                }

                return response()->json([
                    'respuesta' => $respuesta,
                    'reprobados' => $listadoReprobados,
                    'aprobados' => $listadoAprobados,
                ]);
            }


            // 🔁 Retroalimentación específica para estudiante y materia
            if (Str::contains($mensaje, 'retroalimentación para')) {
                $nombreEstudiante = null;
                $materia = null;

                preg_match('/retroalimentaci[oó]n para (.*?) en (.*)/i', $mensaje, $coincidencias);
                if (count($coincidencias) >= 3) {
                    $nombreEstudiante = trim($coincidencias[1]);
                    $materia = trim($coincidencias[2]);
                }

                if (!$nombreEstudiante || !$materia) {
                    return response()->json([
                        'respuesta' => '⚠️ Usa el formato: "retroalimentación para NOMBRE en MATERIA".'
                    ]);
                }

                $cursos = $this->obtenerCursosDelDocenteDesdeMoodle((int)$userIdMoodle);
                $nombreEstudiante = Str::lower(Str::ascii($nombreEstudiante));
                $materia = Str::lower(Str::ascii($materia));
                $respuestaIA = null;

                foreach ($cursos as $curso) {
                    if (!Str::contains(Str::lower(Str::ascii($curso['fullname'])), $materia)) continue;

                    $notas = $this->gradeService->getFinalGradesFromCourse($curso['id']);

                    foreach ($notas as $nota) {
                        $nombreCompleto = Str::lower(Str::ascii($nota['user_fullname']));
                        if (!isset($nota['finalgrade'])) continue;

                        if (Str::contains($nombreCompleto, $nombreEstudiante)) {
                            $nombreDocente = 'Profesor ' . $docente->name;
                            $prompt = "Genera una retroalimentación académica personalizada para el estudiante {$nota['user_fullname']}, que ha obtenido una nota de {$nota['finalgrade']} en la materia {$curso['fullname']}. Incluye sugerencias claras de mejora, una lección breve de recuperación y una actividad sencilla. Finaliza el mensaje con una despedida como:\n\nAtentamente,\n{$nombreDocente}";


                            $respuestaIA = $this->iaService->generarTexto($prompt);
                            $contenido = $respuestaIA['output'] ?? 'No se pudo generar retroalimentación.';

                            $filenameTxt = 'retroalimentacion_' . Str::slug(now()) . '.txt';
                            Storage::disk('public')->put('ia_docs/' . $filenameTxt, $contenido);
                            $urlTxt = asset('storage/ia_docs/' . $filenameTxt);

                            $pdf = Pdf::loadView('profesor.ia.feedback', compact('contenido'));
                            $filenamePdf = 'retroalimentacion_' . Str::slug(now()) . '.pdf';
                            Storage::disk('public')->put('ia_docs/' . $filenamePdf, $pdf->output());
                            $urlPdf = asset('storage/ia_docs/' . $filenamePdf);

                            return response()->json([
                                'respuesta' => $contenido,
                                'archivo' => $urlTxt,
                                'pdf' => $urlPdf
                            ]);
                        }
                    }
                }

                return response()->json([
                    'respuesta' => "⚠️ No se encontró al estudiante «{$nombreEstudiante}» en un curso que coincida con «{$materia}».",
                ]);
            }
            

            // ✅ Generar retroalimentación directamente desde el PDF subido o desde 'Mis Recursos'
            if (Str::contains($mensaje, ['pdf que subí', 'pdf subido', 'pdf adjunto']) && $contenidoPDF) {
                $prompt = "📄 Se ha utilizado el contenido del PDF más reciente subido por el docente en 'Mis Recursos':\n\n"
                        . $contenidoPDF
                        . "\n\nCon base en ese contenido, genera:\n"
                        . "- Una RETROALIMENTACIÓN académica para un estudiante con bajo rendimiento.\n"
                        . "- Recomendaciones específicas.\n"
                        . "- Un banco de preguntas en formato GIFT para Moodle.\n"
                        . "Empieza cada sección con su título (RETROALIMENTACIÓN:, RECOMENDACIONES:, GIFT:).";

                $respuestaIA = $this->iaService->generarTexto($prompt);
                $contenido = $respuestaIA['output'] ?? 'No se pudo generar retroalimentación desde el PDF.';

                $filenameTxt = 'pdf_feedback_' . Str::slug(now()) . '.txt';
                Storage::disk('public')->put('ia_docs/' . $filenameTxt, $contenido);
                $urlTxt = asset('storage/ia_docs/' . $filenameTxt);

                $pdf = Pdf::loadView('profesor.ia.feedback', compact('contenido'));
                $filenamePdf = 'pdf_feedback_' . Str::slug(now()) . '.pdf';
                Storage::disk('public')->put('ia_docs/' . $filenamePdf, $pdf->output());
                $urlPdf = asset('storage/ia_docs/' . $filenamePdf);

                return response()->json([
                    'respuesta' => $contenido,
                    'archivo' => $urlTxt,
                    'pdf' => $urlPdf
                ]);
            }

            // Definir $urlPdf con valor predeterminado vacío para evitar el error
            $urlPdf = '';

            $contenidoPDF = '';
            if ($request->hasFile('archivo') && $request->file('archivo')->isValid()) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($request->file('archivo')->getPathname());
                    $contenidoPDF = $pdf->getText();
                } catch (\Throwable $e) {
                    Log::error("❌ Error al leer el PDF: " . $e->getMessage());
                    return response()->json([
                        'respuesta' => 'Error al procesar el archivo PDF.',
                    ], 500);
                }
            }

            $prefijo = '';
            if ($contenidoPDF && $mensaje) {
                $prefijo = "El docente escribió lo siguiente: {$mensaje}\n\nAdemás, analiza el siguiente documento PDF:\n\n{$contenidoPDF}";
            } elseif ($contenidoPDF) {
                $prefijo = "Analiza el siguiente documento PDF:\n\n{$contenidoPDF}";
            } elseif ($mensaje) {
                $prefijo = "El docente pregunta: {$mensaje}";
            } else {
                return response()->json(['respuesta' => '❌ Debe ingresar un mensaje o subir un archivo PDF.'], 422);
            }

            // 🧠 Generación de preguntas GIFT desde el PDF subido
            if (
                Str::contains($mensajeNormalizado, 'gift') &&
                Str::contains($mensajeNormalizado, ['genera', 'crear', 'banco de preguntas']) &&
                $contenidoPDF
            ) {
                $prompt = "A partir del siguiente contenido extraído del PDF, genera 10 preguntas en formato GIFT (compatibles con Moodle). Utiliza exclusivamente el contenido del texto. No expliques, solo genera las preguntas.\n\nContenido del PDF:\n" . $contenidoPDF;

                $respuestaIA = $this->iaService->generarTexto($prompt);
                $contenido = $respuestaIA['output'] ?? 'No se pudo generar preguntas GIFT.';

                $filenameGift = 'preguntas_gift_' . Str::slug(now()) . '.gift';
                Storage::disk('public')->put('ia_docs/' . $filenameGift, $contenido);
                $urlGift = asset('storage/ia_docs/' . $filenameGift);

                return response()->json([
                    'respuesta' => '🎁 Banco de preguntas GIFT generado correctamente.',
                    'contenido' => $contenido,
                    'gift' => $urlGift
                ]);
            }

            if ($contenidoPDF && $mensaje) {
                // 🧠 DEBUG LOG: Confirmar entrada
                Log::info("✅ Bloque activo: mensaje + contenidoPDF detectado.");

                // Limitar contenido para evitar exceso de tokens (máximo 3000 caracteres)
                $contenidoReducido = Str::limit($contenidoPDF, 3000, '[...]');

                // Prompt detallado para OpenAI
                $prompt = <<<PROMPT
            📄 Este es el contenido del sílabo extraído desde el PDF:

            {$contenidoReducido}

            📌 El docente solicita lo siguiente:
            "{$mensaje}"

            🎯 Tu tarea es:
            - Analizar el contenido del sílabo.
            - Generar una **RETROALIMENTACIÓN académica breve y motivadora** para los estudiantes que han reprobado.
            - Generar **5 preguntas tipo test en formato GIFT** sobre los temas tratados (auditoría TI, riesgos, peritaje, etc.).
            - NO EXPLIQUES el proceso. SOLO entrega los resultados.
            - Usa solamente el contenido del sílabo, no inventes.

            ✅ Estructura de respuesta requerida:

            RETROALIMENTACIÓN:
            [Texto motivacional personalizado aquí]

            PREGUNTAS GIFT:
            [PREGUNTA 1 en GIFT]
            [PREGUNTA 2 en GIFT]
            ...
            PROMPT;

                // 🧠 DEBUG LOG: Prompt generado
                Log::info("🧠 Prompt enviado a OpenAI:", ['prompt' => $prompt]);

                // Generar respuesta de IA
                $respuestaIA = $this->iaService->generarTexto($prompt);
                $contenidoRespuesta = $respuestaIA['output'] ?? '❌ No se pudo generar la respuesta.';

                // 🧠 DEBUG LOG: Respuesta de OpenAI
                Log::info("📥 Respuesta recibida de OpenAI:", ['respuesta' => $contenidoRespuesta]);

                // Guardar como archivo .txt en /public/ia_docs/
                $filename = 'respuesta_pdf_' . Str::slug(now()) . '.txt';
                Storage::disk('public')->put('ia_docs/' . $filename, $contenidoRespuesta);
                $urlTxt = asset('storage/ia_docs/' . $filename);

                return response()->json([
                    'respuesta' => $contenidoRespuesta,
                    'archivo' => $urlTxt
                ]);
            }


            // 🧠 Consulta general
            $respuestaIA = $this->iaService->generarTexto($prefijo);
            $contenido = $respuestaIA['output'] ?? 'Respuesta vacía.';

            $filenameTxt = 'respuesta_' . Str::slug(now()) . '.txt';
            Storage::disk('public')->put('ia_docs/' . $filenameTxt, $contenido);
            $urlTxt = asset('storage/ia_docs/' . $filenameTxt);

            $pdf = Pdf::loadView('profesor.ia.feedback', compact('contenido'));
            $filenamePdf = 'respuesta_' . Str::slug(now()) . '.pdf';
            Storage::disk('public')->put('ia_docs/' . $filenamePdf, $pdf->output());
            $urlPdf = asset('storage/ia_docs/' . $filenamePdf);

            return response()->json([
                'respuesta' => $contenido,
                'archivo' => $urlTxt,
                'pdf' => $urlPdf
            ]);

        } catch (\Throwable $e) {
            Log::error("💥 Error IA: " . $e->getMessage());
            return response()->json(['error' => 'Error al procesar la consulta.'], 500);
        }
    }

    private function obtenerCursosDelDocenteDesdeMoodle(int $userIdMoodle): array
    {
        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'core_enrol_get_users_courses',
            'moodlewsrestformat' => 'json',
            'userid' => $userIdMoodle,
        ]);

        $json = $response->json();

        if (isset($json['exception'])) {
            Log::error("❌ Moodle error: " . $json['message']);
            return [];
        }

        return $json ?? [];
    }

    public function generarArchivo(Request $request)
    {
        $request->validate(['contenido' => 'required|string|max:5000']);

        try {
            $contenido = $request->contenido;
            $filename = 'respuesta_' . Str::slug(now()) . '.txt';
            $path = 'ia_docs/' . $filename;
            Storage::put($path, $contenido);
            $url = route('docente.ia.descargar', ['filename' => $filename]);

            return response()->json(['archivo' => $url]);
        } catch (\Throwable $e) {
            Log::error('❌ Error al generar archivo IA: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo generar el archivo.'], 500);
        }
    }

    public function generarPDFDesdeIA(Request $request)
    {
        $request->validate(['contenido' => 'required|string|max:5000']);

        try {
            $contenido = $request->input('contenido');
            $pdf = Pdf::loadView('profesor.ia.feedback', compact('contenido'));
            return $pdf->download('retroalimentacion_ia.pdf');
        } catch (\Throwable $e) {
            Log::error('❌ Error al generar PDF IA: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo generar el PDF.'], 500);
        }
    }

    public function generarReporteCompleto()
    {
        $docente = Auth::user();
        $userIdMoodle = $docente->id_user_moodle;

        if (!$userIdMoodle) {
            return back()->with('error', 'El docente no tiene ID Moodle asignado.');
        }

        $cursos = $this->obtenerCursosDelDocenteDesdeMoodle((int)$userIdMoodle);
        $listado = [];

        foreach ($cursos as $curso) {
            $notas = $this->gradeService->getFinalGradesFromCourse($curso['id']);
            $estudiantes = [];

            foreach ($notas as $nota) {
                if (!isset($nota['finalgrade'])) continue;

                $final = round($nota['finalgrade'], 2);
                $estado = $final < 7 ? 'Reprobado' : 'Aprobado';
                $retro = $final < 7
                    ? $this->iaService->generarTexto("Genera una retroalimentación breve para el estudiante {$nota['user_fullname']} que ha reprobado la materia {$curso['fullname']} con nota $final.")['output']
                    : null;

                $estudiantes[] = [
                    'nombre' => $nota['user_fullname'],
                    'nota' => $final,
                    'estado' => $estado,
                    'retro' => $retro
                ];
            }

            $recomendacion = $this->iaService->generarTexto("Genera una recomendación para el docente del curso {$curso['fullname']} sobre cómo mejorar el rendimiento del grupo.")['output'];

            $listado[] = [
                'curso' => $curso['fullname'],
                'estudiantes' => $estudiantes,
                'recomendacion' => $recomendacion
            ];
        }

        $pdf = Pdf::loadView('profesor.ia.reporte_curso_pdf', compact('listado'));
        return $pdf->download('reporte_completo_IA.pdf');
    }

    public function subirArchivo(Request $request)
    {
        try {
            if (!$request->hasFile('archivo')) {
                return response()->json([
                    'respuesta' => '❌ No se recibió ningún archivo PDF.',
                ], 400);
            }

            $archivo = $request->file('archivo');

            if (!$archivo->isValid() || $archivo->getClientOriginalExtension() !== 'pdf') {
                return response()->json([
                    'respuesta' => '❌ El archivo no es válido o no es un PDF.',
                ], 400);
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($archivo->getPathname());
            $contenido = $pdf->getText();

            if (empty($contenido)) {
                return response()->json([
                    'respuesta' => '⚠️ No se pudo extraer texto del PDF.',
                ], 400);
            }

            // Guardar el contenido en sesión
            Session::put('texto_pdf', $contenido);

            // Confirmación
            return response()->json([
                'respuesta' => '✅ PDF recibido y procesado correctamente. Puedes hacer una pregunta sobre su contenido.'
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error al procesar PDF: ' . $e->getMessage());
            return response()->json([
                'respuesta' => '❌ Error interno al analizar el archivo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generarDesdeRecursos($curso_id, $area_id, $estudiante_id)
    {
        // 1. Buscar PDF asociado al curso y materia
        $contenido = Contenido::where('curso_moodle_id', $curso_id)
                            ->where('area_id', $area_id)
                            ->latest()
                            ->first();

        if (!$contenido || !Storage::disk('public')->exists($contenido->archivo)) {
            return back()->withErrors(['archivo' => 'No hay recursos disponibles.']);
        }

        // 2. Leer contenido del PDF
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile(storage_path('app/public/' . $contenido->archivo));
        $textoPdf = $pdf->getText();

        // 3. Obtener nota final del estudiante desde Moodle
        $notas = app(\App\Services\MoodleGradeService::class)->getFinalGradesFromCourse($curso_id);
        $notaFinal = collect($notas)->firstWhere('user_id', $estudiante_id)['finalgrade'] ?? null;

        if (is_null($notaFinal)) {
            return back()->withErrors(['nota' => 'No se pudo obtener la nota del estudiante.']);
        }

        // 4. Construir prompt para IA
        $prompt = "El siguiente contenido ha sido extraído del recurso PDF del curso '{$contenido->curso_nombre}' del área ID {$area_id}:\n\n"
        . $textoPdf
        . "\n\nLa nota del estudiante es: {$notaFinal}. A partir de este contenido, genera:\n"
        . "- Una RETROALIMENTACIÓN personalizada\n"
        . "- RECOMENDACIONES específicas\n"
        . "- Un banco de 5 preguntas en formato GIFT para Moodle.\n"
        . "Empieza cada sección con su título (RETROALIMENTACIÓN:, RECOMENDACIONES:, GIFT:).";


        // 5. Enviar a IA
        $respuesta = app(\App\Services\OpenAIService::class)->generarTexto($prompt);
        $output = $respuesta['output'] ?? '';

        // 6. Extraer secciones
        $retro = extraerSeccion($output, 'RETROALIMENTACIÓN');
        $recom = extraerSeccion($output, 'RECOMENDACIONES');
        $gift = extraerSeccion($output, 'GIFT');

        // 7. Guardar en base de datos
        \App\Models\Feedback::create([
            'user_id' => $estudiante_id,
            'curso_id' => $curso_id,
            'area_id' => $area_id,
            'retroalimentacion' => $retro,
            'recomendaciones' => $recom,
            'gift' => $gift,
        ]);

        // 8. Guardar archivo GIFT en storage público
        $giftPath = "ia_exports/gift_{$estudiante_id}_{$curso_id}.gift";
        Storage::disk('public')->put($giftPath, $gift);

        // 9. Mostrar vista con descarga del GIFT
        return view('profesor.ia.feedback', [
            'retro' => $retro,
            'recom' => $recom,
            'giftPath' => asset('storage/' . $giftPath)
        ]);
    }
}

// 👇 Esto va después del cierre de la clase RetroalimentacionAIController

if (!function_exists('extraerSeccion')) {
    /**
     * Extrae una sección específica desde un texto plano.
     *
     * @param string $contenido Texto completo (por ejemplo, extraído de un PDF)
     * @param string $tituloBuscado Título de la sección a buscar
     * @return string|null Sección encontrada o null si no existe
     */
    function extraerSeccion(string $contenido, string $tituloBuscado): ?string
    {
        $tituloEscapado = preg_quote($tituloBuscado, '/');
        $patron = "/{$tituloEscapado}[\s\n\r]*:(.*?)(?=\n[A-ZÁÉÍÓÚÑ ]{2,}:|\Z)/siu";

        if (preg_match($patron, $contenido, $coincidencias)) {
            return trim($coincidencias[1]);
        }

        return null;
    }
}

