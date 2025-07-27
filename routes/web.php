<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\CheckRole;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\PlantillaController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\GradoController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RetroalimentacionAIController;
use App\Http\Controllers\VisualizacionController;
use App\Http\Controllers\ContenidoController;


// =====================
// RUTA INICIAL
// =====================
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();
    if ($user->email === 'admin@colegiopceirafaelgaleth.com') {
        return redirect()->route('admin.dashboard');
    } elseif ($user->rol === 'profesor') {
        return redirect()->route('profesor.dashboard');
    }

    return redirect()->route('login');
});

// =====================
// LOGIN / LOGOUT
// =====================
Route::get('/login', [LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// =====================
// RUTAS PROTEGIDAS
// =====================
Route::middleware('auth')->group(function () {

    // Ruta para mostrar los cursos asignados al profesor
    Route::get('/profesor/cursos', [ProfesorController::class, 'cursos'])
        ->middleware(CheckRole::class . ':profesor')
        ->name('profesor.cursos.index'); // Esta es la ruta para los cursos del profesor

    // ✅ Ruta para mostrar los estudiantes matriculados por curso del profesor
    Route::get('/profesor/matriculas', [ProfesorController::class, 'verMatriculas'])
        ->middleware(CheckRole::class . ':profesor')
        ->name('profesor.matriculas');
        // DASHBOARD ADMIN
    Route::get('/admin/dashboard', [AdminController::class, 'index'])
        ->middleware(CheckRole::class . ':admin')
        ->name('admin.dashboard');


     // Visualización
    Route::get('/visualizacion', [VisualizacionController::class, 'index'])->name('visualizacion.index');
    Route::get('/visualizacion/datos/{cursoId}', [VisualizacionController::class, 'datosCurso']);
     Route::get('/visualizacion/tareas/{cursoId}', [VisualizacionController::class, 'tareasCurso']);
    


    // DASHBOARD PROFESOR
    Route::get('/profesor/dashboard', [ProfesorController::class, 'index'])
        ->middleware(CheckRole::class . ':profesor')
        ->name('profesor.dashboard');

    Route::get('/curso/{curso}', [CursoController::class, 'show'])->name('curso.detalles');
    
    // PANEL DOCENTES
    Route::prefix('dashboard')->group(function () {
        Route::get('/docentes', [DashboardController::class, 'index'])->name('dashboard.docentes.index');
        Route::get('/docentes/{gradoId}/materias', [DashboardController::class, 'materias'])->name('dashboard.materias');
        Route::get('/{courseId}', [DashboardController::class, 'show'])->name('dashboard.show');

        Route::post('/{courseId}/chat-feedback/{userId}', [DashboardController::class, 'chatFeedback'])->name('dashboard.chat.feedback');
        Route::post('/{courseId}/feedback/{userId}', [DashboardController::class, 'regenerateFeedback'])->name('feedbacks.regenerate');
        Route::put('/{courseId}/feedbacks/{userId}', [DashboardController::class, 'updateFeedback'])->name('feedbacks.update');
        Route::get('/{courseId}/nota-final/{userId}', [DashboardController::class, 'obtenerNotaFinal'])->name('dashboard.notaFinal');
    });

    // PERIODOS
    Route::resource('periodos', PeriodoController::class)->names('periodos');
    Route::get('/periodo/{periodo}/consultargrados', [PeriodoController::class, 'consultargrados'])->name('periodo.consultargrados');

    // USUARIOS
    Route::resource('users', UserController::class)->names('users');
    Route::get('/usuarios/csv', [UserController::class, 'formularioCsv']);
    Route::post('/usuarios/importar-csv', [UserController::class, 'importarCsv'])->name('usuarios.importarCsv');
    Route::get('/usuarios/exportar', [UserController::class, 'exportarCsv'])->name('usuarios.exportarCsv');

    // PLANTILLAS
    Route::resource('plantillas', PlantillaController::class)->names('plantillas');

    // CURSOS
    Route::resource('cursos', CursoController::class)->names('cursos');
    Route::get('curso/{plantilla}/crearcurso', [CursoController::class, 'crearcurso'])->name('curso.crearcurso');
    Route::post('curso/{plantilla}/store2', [CursoController::class, 'store2'])->name('curso.store2');
    Route::delete('curso/{curso}/{plantilla}/destroy2', [CursoController::class, 'destroy2'])->name('curso.destroy2');

    // GRADOS
    Route::resource('grados', GradoController::class)->names('grados');
    Route::get('grado/{periodo}/creargrado', [GradoController::class, 'creargrado'])->name('grado.creargrado');
    Route::post('grado/{periodo}/store2', [GradoController::class, 'store2'])->name('grado.store2');
    Route::delete('/grados/{id}', [GradoController::class, 'destroy'])->name('grado.destroy');
    Route::get('/grados/{grado}/consultarmatricula', [GradoController::class, 'consultarmatricula'])->name('grado.consultarmatricula');
    Route::post('grado/{grado}/matricular', [GradoController::class, 'matricular'])->name('grado.matricular');
    Route::post('/grados/{grado}/desmatricular', [GradoController::class, 'desmatricular'])->name('grado.desmatricular');
    Route::get('/grados/{grado}/desmatricular/{user}', [GradoController::class, 'desmatricular'])->name('grado.desmatricular.individual');

    // NOTAS
    Route::get('/grados/{grado}/usuarios/{user}/notas', [GradoController::class, 'consultarnotas'])->name('grado.consultarnotas');
    Route::get('/grados/{grado}/usuarios/{user}/area/{area}/detalle', [GradoController::class, 'verDetalleNota'])->name('grado.verDetalleNota');
    Route::get('/grados/{grado}/usuarios/{user}/area/{area}/editar', [GradoController::class, 'editarNota'])->name('grado.editarNota');
    Route::get('grados/{grado}/notas/{user}/pdf', [GradoController::class, 'generarPDF'])->name('grado.generarPDF');
    Route::get('grados/{grado}/usuarios/{user}/notas.pdf', [GradoController::class, 'generarPDF'])->name('grados.notaspdf');

    // ÁREAS
    Route::resource('areas', AreaController::class)->names('areas');

    // IA - Asistente para Docentes
    Route::middleware(['auth', 'can:is-profesor'])->group(function () {

        // Mostrar vista principal del chat IA (GET)
        Route::get('/docente/asistente-ia', [RetroalimentacionAIController::class, 'viewDocente'])
            ->name('docente.ia.view');

        // Procesamiento de preguntas con IA (POST)
        Route::post('/docente/asistente-ia', [RetroalimentacionAIController::class, 'procesarDocente'])
            ->name('docente.ia.procesar');

        // Reporte IA completo (GET)
        Route::get('/docente/asistente-ia/reporte-completo', [RetroalimentacionAIController::class, 'generarReporteCompleto'])
            ->name('docente.ia.reporteCompleto');

        // Subir Archivos IA (POST)
        Route::post('/docente/asistente-ia/subir-archivo', [RetroalimentacionAIController::class, 'subirArchivo'])
            ->name('docente.ia.subirArchivo');

        // Generar archivo con respuesta IA (POST)
        Route::post('/docente/asistente-ia/archivo', [RetroalimentacionAIController::class, 'generarArchivo'])
            ->name('docente.ia.generarArchivo');

        // Descargar archivo generado (GET)
        Route::get('/docente/asistente-ia/descargar/{filename}', function ($filename) {
            $path = storage_path("app/ia_docs/{$filename}");

            if (!file_exists($path)) {
                abort(404);
            }

            return response()->download($path);
        })->name('docente.ia.descargar');

        // Generar PDF desde IA (POST)
        Route::post('/docente/asistente-ia/pdf', [RetroalimentacionAIController::class, 'generarPDFDesdeIA'])
            ->name('docente.ia.generarPDF');

        // Generar desde recursos PDF/texto (POST)
        Route::post('/ia/generar-desde-recursos', [RetroalimentacionAIController::class, 'generarDesdeRecursos'])
            ->name('ia.generarDesdeRecursos');

        // Recursos del profesor (subir y listar)
        Route::middleware(['auth'])->prefix('profesor/recursos')->group(function () {
            Route::get('/', [ContenidoController::class, 'index'])->name('recursos.index');
            Route::post('/', [ContenidoController::class, 'store'])->name('recursos.store');
            Route::get('profesor/matriculas/pdf/{courseId}', [ProfesorController::class, 'descargarMatriculasPdf'])->name('profesor.matriculas.pdf');
            Route::get('profesor/reporte/generar-pdf/{cursoId}', [ReporteController::class, 'generarRecursoPdf'])->name('profesor.reporte.generarPdf');
            Route::get('/profesor/reporte/tareas/{userId}/{cursoId}', [ReporteController::class, 'verTareas'])
            ->name('profesor.reporte.tareas');

        });

        // Eliminar recurso
        Route::delete('/recursos/{id}', [ContenidoController::class, 'destroy'])->name('recursos.destroy');
    });


});
