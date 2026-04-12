<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetoCompletado extends Model
{
    protected $table = 'reto_completados';

    protected $fillable = [
        'usuario_id',
        'reto_id',
        'evidencia_url_reto_completado',
        'xp_ganado_reto_completado',
        'estado_reto_completado',
        'motivo_rechazo_reto_completado',
    ];

    protected $casts = [
        'xp_ganado_reto_completado' => 'integer',
    ];

    // Pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Pertenece a un reto
    public function reto()
    {
        return $this->belongsTo(Reto::class, 'reto_id');
    }

    // Tiene una publicación (opcional)
    public function publicacion()
    {
        return $this->hasOne(Publicacion::class, 'reto_completado_id');
    }

    // ─── SCOPE: solo aprobados ─────────────────────────────────────
    public function scopeAprobados($query)
    {
        return $query->where('estado_reto_completado', 'aprobado');
    }
}