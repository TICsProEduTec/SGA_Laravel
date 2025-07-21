<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'id_category_moodle'];

    // Relación: un Periodo tiene muchos Grados
    public function grados() {
        return $this->hasMany(Grado::class, 'periodo_id');
    }
}
