<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'shortname', 'id_course_moodle', 'grado_id', 'nota'];

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }
}
