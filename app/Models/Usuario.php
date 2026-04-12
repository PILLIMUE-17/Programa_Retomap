<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre_usuario',
        'email_usuario',
        'username_usuario',
        'password_hash_usuario',
        'avatar_url_usuario',
        'xp_total_usuario',
        'racha_dias_usuario',
        'nivel_id',
        'ciudad_usuario',
        'activo_usuario',
        'ultimo_acceso_usuario',
        "es_admin",
    ];

    // Columnas que NUNCA se devuelven en respuestas JSON
    protected $hidden = [
        'password_hash_usuario',
    ];

    protected $casts = [
        'activo_usuario'         => 'boolean',
        'ultimo_acceso_usuario'  => 'datetime',
        'xp_total_usuario'       => 'integer',
        'racha_dias_usuario'     => 'integer',
        "es_admin"        => 'boolean',
    ];

    // ─── RELACIONES ────────────────────────────────────────────────

    // Un usuario pertenece a un nivel
    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    // Un usuario ha completado muchos retos
    public function retosCompletados()
    {
        return $this->hasMany(RetoCompletado::class, 'usuario_id');
    }

    // Un usuario tiene muchas publicaciones
    public function publicaciones()
    {
        return $this->hasMany(Publicacion::class, 'usuario_id');
    }

    // Un usuario tiene muchas insignias (muchos a muchos)
    public function insignias()
    {
        return $this->belongsToMany(
            Insignia::class,
            'usuario_insignias',    // tabla pivot
            'usuario_id',           // FK de este modelo en la pivot
            'insignia_id'           // FK del otro modelo en la pivot
        )->withPivot('obtenida_en_usuario_insignia');
    }

    // Amistades enviadas por este usuario
    public function amistades()
    {
        return $this->hasMany(Amistad::class, 'solicitante_id');
    }

    // Amistades recibidas por este usuario
    public function solicitudesRecibidas()
    {
        return $this->hasMany(Amistad::class, 'receptor_id');
    }

    // Canjes realizados
    public function canjes()
    {
        return $this->hasMany(Canje::class, 'usuario_id');
    }

    // Notificaciones
    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    // Likes dados
    public function likesPublicaciones()
    {
        return $this->hasMany(LikePublicacion::class, 'usuario_id');
    }

    // ─── HELPERS ───────────────────────────────────────────────────

    // Sobrescribir el campo de contraseña para Sanctum/Auth
    public function getAuthPassword()
    {
        return $this->password_hash_usuario;
    }
}