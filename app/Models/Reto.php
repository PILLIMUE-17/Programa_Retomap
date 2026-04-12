<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reto extends Model
{
    use SoftDeletes;

    protected $table = 'retos';

    protected $fillable = [
        'nombre_reto',
        'descripcion_reto',
        'xp_recompensa_reto',
        'instruccion_evidencia_reto',
        'expira_en_reto',
        'activo_reto',
        'veces_completado_reto',
        'dificultad_reto',
        'lugar_id',
        'tipo_reto_id',
    ];

    protected $casts = [
        'activo_reto'      => 'boolean',
        'expira_en_reto'   => 'datetime',
        'dificultad_reto'  => 'integer',
        'xp_recompensa_reto' => 'integer',
    ];

    // Un reto pertenece a un lugar
    public function lugar()
    {
        return $this->belongsTo(Lugar::class, 'lugar_id');
    }

    // Un reto pertenece a un tipo
    public function tipoReto()
    {
        return $this->belongsTo(TipoReto::class, 'tipo_reto_id');
    }

    // Un reto tiene muchos completados
    public function completados()
    {
        return $this->hasMany(RetoCompletado::class, 'reto_id');
    }

    // ─── HELPER: ¿El reto está vigente? ───────────────────────────
    public function estaVigente(): bool
    {
        if (!$this->activo_reto) return false;
        if ($this->expira_en_reto && $this->expira_en_reto->isPast()) return false;
        return true;
    }

    // ─── SCOPE: solo retos activos ────────────────────────────────
    // Uso: Reto::activos()->get()
    public function scopeActivos($query)
    {
        return $query->where('activo_reto', true)
                     ->where(function ($q) {
                         $q->whereNull('expira_en_reto')
                           ->orWhere('expira_en_reto', '>', now());
                     });
    }
}