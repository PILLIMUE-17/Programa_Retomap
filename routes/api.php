<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LugarController;
use App\Http\Controllers\RetoController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\AmistadController;
use App\Http\Controllers\BeneficioController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\AdminRetoController;
use App\Http\Controllers\AdminAliadoController;
use App\Http\Controllers\AdminLugarController;
use App\Http\Controllers\AdminBeneficioController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UploadController;


// ─────────────────────────────────────────────────────────────
// RUTAS PÚBLICAS — no requieren token
// ─────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/password/forgot', [PasswordResetController::class, 'forgot']);
Route::post('/password/reset',  [PasswordResetController::class, 'reset']);


// ─────────────────────────────────────────────────────────────
// RUTAS PROTEGIDAS — requieren token en el header:
// Authorization: Bearer <token>
// ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/upload/evidencia', [UploadController::class, 'evidencia']);

    // ── Lugares ────────────────────────────────────
    Route::prefix('lugares')->group(function () {
        Route::get('/',            [LugarController::class, 'index']);      // GET /api/lugares
        Route::get('/cercanos',    [LugarController::class, 'cercanos']);   // GET /api/lugares/cercanos
        Route::get('/municipios',  [LugarController::class, 'municipios']); // GET /api/lugares/municipios
        Route::get('/{id}',        [LugarController::class, 'show']);       // GET /api/lugares/1
    });

    // ── Retos ──────────────────────────────────────────
    Route::prefix('retos')->group(function () {
        Route::get('/',                  [RetoController::class, 'index']);          // GET  /api/retos
        Route::get('/completados',       [RetoController::class, 'misCompletados']); // GET  /api/retos/completados
        Route::get('/{id}',              [RetoController::class, 'show']);           // GET  /api/retos/1
        Route::post('/{id}/completar',   [RetoController::class, 'completar']);      // POST /api/retos/1/completar
    });

    // ── Publicaciones ──────────────────────────────────
    Route::get('/feed', [PublicacionController::class, 'feed']);   // GET /api/feed

    Route::prefix('publicaciones')->group(function () {
        Route::post('/',                    [PublicacionController::class, 'store']);       // POST   /api/publicaciones
        Route::get('/usuario/{id}',         [PublicacionController::class, 'porUsuario']); // GET    /api/publicaciones/usuario/1
        Route::get('/{id}',                 [PublicacionController::class, 'show']);        // GET    /api/publicaciones/1
        Route::delete('/{id}',              [PublicacionController::class, 'destroy']);     // DELETE /api/publicaciones/1
        Route::post('/{id}/like',           [PublicacionController::class, 'toggleLike']); // POST   /api/publicaciones/1/like
        Route::post('/{id}/comentar',       [PublicacionController::class, 'comentar']);   // POST   /api/publicaciones/1/comentar
    });
    
    // ── Perfil ─────────────────────────────────────────
    Route::prefix('perfil')->group(function () {
        Route::get('/',          [PerfilController::class, 'miPerfil']);       // GET /api/perfil
        Route::put('/',          [PerfilController::class, 'actualizar']);     // PUT /api/perfil
        Route::put('/password',  [PerfilController::class, 'cambiarPassword']); // PUT /api/perfil/password
        Route::get('/buscar',     [PerfilController::class, 'buscarUsuarios']);        // GET /api/perfil/buscar?termino=abc
        Route::get('/{id}',      [PerfilController::class, 'verPerfil']);     // GET /api/perfil/5
        
    });

    // ── Ranking ────────────────────────────────────────
    Route::prefix('ranking')->group(function () {
        Route::get('/',            [PerfilController::class, 'ranking']);     // GET /api/ranking
        Route::get('/mi-posicion', [PerfilController::class, 'miPosicion']); // GET /api/ranking/mi-posicion
    });

    // ── Amistades ──────────────────────────────────────
    Route::prefix('amistades')->group(function () {
        Route::get('/',                  [AmistadController::class, 'misAmigos']);    // GET /api/amistades
        Route::get('/pendientes',        [AmistadController::class, 'pendientes']);   // GET /api/amistades/pendientes
        Route::get('/estado/{id}',       [AmistadController::class, 'estado']);       // GET /api/amistades/estado/5
        Route::post('/solicitar/{id}',   [AmistadController::class, 'solicitar']);    // POST /api/amistades/solicitar/5
        Route::put('/{id}/aceptar',      [AmistadController::class, 'aceptar']);      // PUT /api/amistades/1/aceptar
        Route::delete('/{id}/rechazar',  [AmistadController::class, 'rechazar']);     // DELETE /api/amistades/1/rechazar
        Route::put('/bloquear/{id}',     [AmistadController::class, 'bloquear']);     // PUT /api/amistades/bloquear/5
        Route::delete('/{id}',           [AmistadController::class, 'eliminar']);     // DELETE /api/amistades/1
    });
    // ── Beneficios ─────────────────────────────────────
    Route::prefix('beneficios')->group(function () {
        Route::get('/',                        [BeneficioController::class, 'index']);       // GET  /api/beneficios
        Route::get('/mis-canjes',              [BeneficioController::class, 'misCanjes']);   // GET  /api/beneficios/mis-canjes (específica primero)
        Route::put('/canjes/{codigo}/usar',    [BeneficioController::class, 'usarCanje']);   // PUT  /api/beneficios/canjes/ABC12345/usar
        Route::post('/{id}/canjear',           [BeneficioController::class, 'canjear']);     // POST /api/beneficios/1/canjear
        Route::get('/{id}',                    [BeneficioController::class, 'show']);        // GET  /api/beneficios/1 (genérica al final)
    });
    // ── Notificaciones ─────────────────────────────────
    Route::prefix('notificaciones')->group(function () {
        Route::get('/',                      [NotificacionController::class, 'index']);           // GET    /api/notificaciones
        Route::get('/contador',              [NotificacionController::class, 'contador']);        // GET    /api/notificaciones/contador
        Route::put('/leer-todas',            [NotificacionController::class, 'marcarTodasLeidas']); // PUT /api/notificaciones/leer-todas
        Route::delete('/limpiar/leidas',     [NotificacionController::class, 'limpiarLeidas']);   // DELETE /api/notificaciones/limpiar/leidas
        Route::put('/{id}/leer',             [NotificacionController::class, 'marcarLeida']);     // PUT    /api/notificaciones/1/leer
        Route::delete('/{id}',               [NotificacionController::class, 'destroy']);         // DELETE /api/notificaciones/1
    });

});

// ─────────────────────────────────────────────────────────────
// RUTAS ADMIN — requieren token + es_admin = true
// ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/retos-completados',                   [AdminRetoController::class, 'index']);
    Route::put('/retos-completados/{id}/aprobar',      [AdminRetoController::class, 'aprobar']);
    Route::put('/retos-completados/{id}/rechazar',     [AdminRetoController::class, 'rechazar']);
    Route::post('/retos',                               [AdminRetoController::class, 'store']);
    Route::put('/retos/{id}',                          [AdminRetoController::class, 'update']);
    Route::delete('/retos/{id}',                       [AdminRetoController::class, 'destroy']);

    // Rutas para gestionar aliados
    Route::get('/aliados',                            [AdminAliadoController::class, 'index']);
    Route::post('/aliados',                           [AdminAliadoController::class, 'store']);
    Route::put('/aliados/{id}',                      [AdminAliadoController::class, 'update']);
    Route::delete('/aliados/{id}',                   [AdminAliadoController::class, 'destroy']);
    // Rutas para gestionar lugares
    Route::post('/lugares',                           [AdminLugarController::class, 'store']);
    Route::put('/lugares/{id}',                      [AdminLugarController::class, 'update']);
    Route::delete('/lugares/{id}',                   [AdminLugarController::class, 'destroy']);

    // Rutas para gestionar beneficios
    Route::post('/beneficios',                        [AdminBeneficioController::class, 'store']);
    Route::put('/beneficios/{id}',                   [AdminBeneficioController::class, 'update']);
    Route::delete('/beneficios/{id}',                [AdminBeneficioController::class, 'destroy']);

});
