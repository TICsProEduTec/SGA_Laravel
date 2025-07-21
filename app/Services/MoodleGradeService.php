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
        return $this->getFinalGradesFromCourse($courseId);
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
}
