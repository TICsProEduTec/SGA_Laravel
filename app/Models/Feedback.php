<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    // 👇 Asegura que use la tabla correcta
    protected $table = 'feedbacks';

    protected $fillable = [
        'curso_id',
        'user_id',
        'contenido',
        'generado_por',
        'fecha_generado',
    ];

    public $timestamps = true;

    /**
     * Relación con el curso asociado.
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    /**
     * Relación con el usuario (local) al que pertenece este feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
