<?php

// MODELO Grado.php corregido
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'id_category_moodle', 'periodo_id'];

    public function periodo() {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function areas() {
        return $this->hasMany(Area::class, 'grado_id');
    }

    public function cursos() {
        return $this->hasMany(Curso::class, 'Grados_id');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'matriculaciones', 'grado_id', 'user_id');
    }
}