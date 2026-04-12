<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 

class Amistad extends Model
{
    protected $table = 'amistades';
 
    protected $fillable = [
        'solicitante_id',
        'receptor_id',
        'estado_amistad',
    ];
 
    public function solicitante()
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }
 
    public function receptor()
    {
        return $this->belongsTo(Usuario::class, 'receptor_id');
    }
 
    // Scope: solo aceptadas
    public function scopeAceptadas($query)
    {
        return $query->where('estado_amistad', 'aceptada');
    }
}
