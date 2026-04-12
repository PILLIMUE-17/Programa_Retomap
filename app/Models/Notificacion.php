<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
 
    protected $fillable = [
        'usuario_id',
        'tipo_notificacion',
        'titulo_notificacion',
        'cuerpo_notificacion',
        'entidad_tipo_notificacion',
        'entidad_id_notificacion',
        'leida_notificacion',
        'leida_en_notificacion',
    ];
 
    protected $casts = [
        'leida_notificacion'    => 'boolean',
        'leida_en_notificacion' => 'datetime',
    ];
 
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
 
    // Marcar como leída
    public function marcarLeida(): void
    {
        $this->update([
            'leida_notificacion'    => true,
            'leida_en_notificacion' => now(),
        ]);
    }
}