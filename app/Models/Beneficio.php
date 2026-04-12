<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficio extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
 
    protected $table = 'beneficios';
 
    protected $fillable = [
        'aliado_id',
        'descripcion_beneficio',
        'costo_xp_beneficio',
        'valido_hasta_beneficio',
        'cantidad_disponible_beneficio',
        'cantidad_canjeada_beneficio',
        'activo_beneficio',
    ];
 
    protected $casts = [
        'activo_beneficio'             => 'boolean',
        'valido_hasta_beneficio'       => 'datetime',
        'costo_xp_beneficio'           => 'integer',
        'cantidad_disponible_beneficio'=> 'integer',
        'cantidad_canjeada_beneficio'  => 'integer',
    ];
 
    public function aliado()
    {
        return $this->belongsTo(Aliado::class, 'aliado_id');
    }
 
    public function canjes()
    {
        return $this->hasMany(Canje::class, 'beneficio_id');
    }
 
    // Helper: ¿Tiene stock disponible?
    public function tieneStock(): bool
    {
        if (is_null($this->cantidad_disponible_beneficio)) return true; // ilimitado
        return $this->cantidad_canjeada_beneficio < $this->cantidad_disponible_beneficio;
    }
 
    // Helper: ¿Está vigente?
    public function estaVigente(): bool
    {
        if (!$this->activo_beneficio) return false;
        if ($this->valido_hasta_beneficio && $this->valido_hasta_beneficio->isPast()) return false;
        return true;
    }
}