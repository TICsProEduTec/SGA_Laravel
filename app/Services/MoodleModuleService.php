<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleModuleService
{
    public function crearRecursoIA(int $cursoMoodleId, string $titulo, string $contenido): bool
    {
        try {
            $response = Http::asForm()->timeout(60)->post(config('services.moodle.endpoint'), [
                'wstoken' => config('services.moodle.token'),
                'wsfunction' => 'core_courseformat_create_module',
                'moodlewsrestformat' => 'json',
                'courseid' => $cursoMoodleId,
                'modulename' => 'page', // ✅ Tipo de recurso
                'section' => 0,         // Sección 0 por defecto
                'visible' => 1,
                'name' => $titulo,
                'modulename' => 'page',
                'moduledata' => json_encode([
                    'content' => $contenido,
                    'contentformat' => 1, // FORMAT_HTML
                ]),
            ]);

            $data = $response->json();

            if (isset($data['exception'])) {
                Log::error('❌ Error al crear módulo IA en Moodle: ' . json_encode($data));
                return false;
            }

            Log::info("✅ Recurso IA creado en Moodle: {$titulo}");
            return true;
        } catch (\Exception $e) {
            Log::error("💥 Excepción en crearRecursoIA: " . $e->getMessage());
            return false;
        }
    }
}
