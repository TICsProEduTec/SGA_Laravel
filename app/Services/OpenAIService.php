<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public function generarTexto(string $prompt): array
    {
        try {
            $apiKey = config('services.openai.key');

            Log::info("🔐 Enviando solicitud a OpenAI con prompt:");
            Log::info($prompt);

            // 🔄 Conversión a UTF-8 para evitar errores con caracteres mal codificados
            $promptUtf8 = mb_convert_encoding($prompt, 'UTF-8', 'auto');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres una IA educativa que asiste a docentes.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $promptUtf8,
                    ],
                ],
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                Log::error('❌ OpenAI error HTTP: ' . $response->status());
                Log::error('❌ Cuerpo del error: ' . $response->body());
                return ['output' => 'Error al contactar con OpenAI.'];
            }

            $data = $response->json();
            Log::info('🧠 Respuesta completa de OpenAI: ' . json_encode($data));

            $output = $data['choices'][0]['message']['content'] ?? null;

            if (!$output) {
                Log::warning('⚠️ No se encontró respuesta en choices[0][message][content]');
                return ['output' => 'Sin respuesta de IA.'];
            }

            return ['output' => $output];

        } catch (\Exception $e) {
            Log::error("💥 Excepción capturada en OpenAIService: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return ['output' => 'Excepción al generar respuesta.'];
        }
    }

    /**
     * ✅ Método adicional usado por RetroalimentacionAIController
     */
    public function consultar(string $prompt): string
    {
        $resultado = $this->generarTexto($prompt);
        return $resultado['output'] ?? 'Sin contenido';
    }
}
