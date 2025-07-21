<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'Plantillas_id', 'Grados_id', 'id_curso_moodle'];

    public function plantilla() {
        return $this->belongsTo(Plantilla::class, 'Plantillas_id');
    }

    public function grado() {
        return $this->belongsTo(Grado::class, 'Grados_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

}
