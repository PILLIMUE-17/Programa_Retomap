<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Canje extends Model
{
    protected $table = 'canjes';
 
    protected $fillable = [
        'usuario_id',
        'beneficio_id',
        'xp_descontado_canje',
        'codigo_unico_canje',
        'usado_canje',
        'usado_en_canje',
    ];
 
    protected $casts = [
        'usado_canje'       => 'boolean',
        'usado_en_canje'    => 'datetime',
        'xp_descontado_canje' => 'integer',
    ];
 
    // Generar código único automáticamente al crear
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($canje) {
            if (empty($canje->codigo_unico_canje)) {
                $canje->codigo_unico_canje = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
            }
        });
    }
 
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
 
    public function beneficio()
    {
        return $this->belongsTo(Beneficio::class, 'beneficio_id');
    }
}