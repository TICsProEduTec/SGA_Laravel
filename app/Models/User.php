<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'ap_paterno',
        'ap_materno',
        'cedula',
        'email',
        'celular',
        'grado',
        'periodo',
        'id_user_moodle',
        'password',
        'rol', // <- Campo agregado aquí
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relación con grados
    public function grados()
    {
        return $this->belongsToMany(Grado::class, 'matriculaciones', 'user_id', 'grado_id');
    }

    // Relación con feedbacks
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'user_id');
    }

    // Scope para buscar por ID de Moodle
    public function scopeByMoodleId($query, $moodleId)
    {
        return $query->where('id_user_moodle', $moodleId);
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'profesor_id');
    }
}
