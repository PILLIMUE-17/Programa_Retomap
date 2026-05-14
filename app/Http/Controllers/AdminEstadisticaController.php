<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Reto;
use App\Models\Lugar;
use App\Models\RetoCompletado;
use App\Models\Publicacion;
use App\Models\Aliado;
use App\Models\Beneficio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminEstadisticaController extends Controller
{
    // ─────────────────────────────────────────────────────────
    // GET /api/admin/estadisticas
    // Resumen general del sistema
    // ─────────────────────────────────────────────────────────
    public function index()
    {
        $totalUsuarios       = Usuario::count();
        $usuariosActivos     = Usuario::where('activo_usuario', true)->count();
        $totalRetos          = Reto::count();
        $retosActivos        = Reto::where('activo_reto', true)->count();
        $totalLugares        = Lugar::count();
        $totalAliados        = Aliado::where('activo_aliado', true)->count();
        $totalBeneficios     = Beneficio::where('activo_beneficio', true)->count();

        $retosCompletados    = RetoCompletado::count();
        $retosPendientes     = RetoCompletado::where('estado_reto_completado', 'pendiente')->count();
        $retosAprobados      = RetoCompletado::where('estado_reto_completado', 'aprobado')->count();
        $retosRechazados     = RetoCompletado::where('estado_reto_completado', 'rechazado')->count();

        $totalPublicaciones  = Publicacion::count();

        // Usuarios registrados en los últimos 7 días
        $nuevosUsuarios7d = Usuario::where('created_at', '>=', now()->subDays(7))->count();

        // Retos completados en los últimos 7 días
        $completados7d = RetoCompletado::where('created_at', '>=', now()->subDays(7))->count();

        // Top 5 retos más completados
        $topRetos = RetoCompletado::select('reto_id', DB::raw('COUNT(*) as total'))
            ->groupBy('reto_id')
            ->orderByDesc('total')
            ->limit(5)
            ->with('reto:id,nombre_reto')
            ->get();

        // Top 5 usuarios con más XP
        $topUsuarios = Usuario::orderByDesc('xp_total_usuario')
            ->limit(5)
            ->select('id', 'nombre_usuario', 'username_usuario', 'xp_total_usuario', 'avatar_url_usuario')
            ->get();

        return response()->json([
            'resumen' => [
                'total_usuarios'       => $totalUsuarios,
                'usuarios_activos'     => $usuariosActivos,
                'nuevos_usuarios_7d'   => $nuevosUsuarios7d,
                'total_retos'          => $totalRetos,
                'retos_activos'        => $retosActivos,
                'total_lugares'        => $totalLugares,
                'total_aliados'        => $totalAliados,
                'total_beneficios'     => $totalBeneficios,
                'total_publicaciones'  => $totalPublicaciones,
            ],
            'evidencias' => [
                'total'      => $retosCompletados,
                'pendientes' => $retosPendientes,
                'aprobados'  => $retosAprobados,
                'rechazados' => $retosRechazados,
                'completados_7d' => $completados7d,
            ],
            'top_retos'    => $topRetos,
            'top_usuarios' => $topUsuarios,
        ]);
    }
}
