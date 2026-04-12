<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lugar extends Model
{
    use SoftDeletes;

    protected $table = 'lugares';

    protected $fillable = [
        'nombre_lugar',
        'descripcion_lugar',
        'latitud_lugar',
        'longitud_lugar',
        'direccion_lugar',
        'municipio_lugar',
        'departamento_lugar',
        'verificado_lugar',
        'imagen_url_lugar',
        'horario_lugar',
        'calificacion_promedio_lugar',
        'categoria_id',
        'aliado_id',
    ];

    protected $casts = [
        'verificado_lugar'            => 'boolean',
        'latitud_lugar'               => 'float',
        'longitud_lugar'              => 'float',
        'calificacion_promedio_lugar' => 'integer',
    ];

    // Un lugar pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    // Un lugar pertenece a un aliado (opcional)
    public function aliado()
    {
        return $this->belongsTo(Aliado::class, 'aliado_id');
    }

    // Un lugar tiene muchos retos
    public function retos()
    {
        return $this->hasMany(Reto::class, 'lugar_id');
    }

    // ─── HELPER: distancia desde coordenadas dadas ─────────────────
    // Uso: $lugar->distanciaDesde(2.9262, -75.2892)
    public function distanciaDesde(float $lat, float $lng): float
    {
        $radioTierra = 6371; // km
        $dLat = deg2rad($this->latitud_lugar - $lat);
        $dLng = deg2rad($this->longitud_lugar - $lng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad($this->latitud_lugar)) * sin($dLng / 2) ** 2;

        return $radioTierra * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}