<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contenido extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'curso_moodle_id',
        'curso_nombre',
        'archivo',
        'area_id', // ➕ añadido para vincular con la materia
    ];

    /**
     * Relación con el docente (usuario)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el área (materia)
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
