<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleGradeService
{
    protected string $endpoint;
    protected string $token;

    public function __construct()
    {
        $this->endpoint = config('services.moodle.endpoint');
        $this->token    = config('services.moodle.token');
    }

    /**
     * Obtiene el promedio final calculado por Moodle (Total del curso).
     * También incluye el ID local del usuario si existe en la base de datos.
     */
    public function getFinalGradesFromCourse(int $courseId): array
    {
        $response = Http::asForm()->post(config('services.moodle.endpoint'), [
            'wstoken' => config('services.moodle.token'),
            'wsfunction' => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId,
        ]);

        $json = $response->json();

        if (!isset($json['usergrades'])) {
            return [];
        }

        $resultados = [];

        foreach ($json['usergrades'] as $userGrade) {
            $finalGrade = null;
            foreach ($userGrade['gradeitems'] as $item) {
                if ($item['itemtype'] === 'course') {
                    $finalGrade = floatval($item['gradeformatted']);
                    break;
                }
            }

            $resultados[] = [
                'user_id' => $userGrade['userid'],
                'user_fullname' => $userGrade['userfullname'],
                'finalgrade' => $finalGrade,
            ];
        }

        return $resultados;
    }

    /**
     * Alias para mantener compatibilidad con otros controladores.
     * Devuelve las mismas notas finales que getFinalGradesFromCourse().
     */
    public function getCourseGradesWithAverages(int $courseId): array
    {
        $response = Http::asForm()->post($this->endpoint, [
            'wstoken' => $this->token,
            'wsfunction' => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId
        ]);

        if ($response->failed()) {
            Log::error('❌ Moodle WS error al obtener items de notas.', ['response' => $response->body()]);
            return [];
        }

        $data = $response->json();
        $result = [];

        foreach ($data['usergrades'] ?? [] as $user) {
            $fullname = $user['userfullname'] ?? 'Desconocido';
            $items = $user['gradeitems'] ?? [];

            $suma = 0;
            $conteo = 0;
            $detalleItems = [];

            foreach ($items as $item) {
                // Ignorar "Total del curso"
                if ($item['itemtype'] === 'course') continue;

                if (isset($item['graderaw']) && is_numeric($item['graderaw'])) {
                    $suma += $item['graderaw'];
                    $conteo++;
                    $detalleItems[] = [
                        'itemname' => $item['itemname'],
                        'grade' => $item['graderaw'],
                        'itemtype' => $item['itemtype']
                    ];
                }
            }

            if ($conteo === 0) continue;

            $result[] = [
                'user_fullname' => $fullname,
                'items' => $detalleItems,
                'average' => round($suma / $conteo, 2)
            ];
        }

        return $result;
    }


    /**
     * ✅ Nuevo: Obtener la nota final de un usuario específico en un curso.
     */
    public function obtenerNotaFinal(int $courseId, int $userId): ?float
    {
        $notas = $this->getFinalGradesFromCourse($courseId);

        foreach ($notas as $nota) {
            if ($nota['user_id'] == $userId) {
                return $nota['finalgrade'];
            }
        }

        return null;
    }

        /**
     * 🔄 Cálculo manual del promedio si Moodle no calcula el total del curso.
     */
    public function getFinalGradesManual(int $courseId): array
    {
        $response = Http::asForm()->post($this->endpoint, [
            'wstoken' => $this->token,
            'wsfunction' => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid' => $courseId,
        ]);

        $json = $response->json();

        if (!isset($json['usergrades'])) return [];

        $resultados = [];

        foreach ($json['usergrades'] as $userGrade) {
            $suma = 0;
            $conteo = 0;

            foreach ($userGrade['gradeitems'] as $item) {
                if ($item['itemtype'] === 'mod' && isset($item['graderaw']) && is_numeric($item['graderaw'])) {
                    $suma += floatval($item['graderaw']);
                    $conteo++;
                }
            }

            $promedio = $conteo > 0 ? round($suma / $conteo, 2) : null;

            $resultados[] = [
                'user_id' => $userGrade['userid'],
                'user_fullname' => $userGrade['userfullname'],
                'finalgrade' => $promedio,
            ];
        }

        return $resultados;
    }

}
