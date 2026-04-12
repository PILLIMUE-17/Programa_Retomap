<?php

namespace App\Observers;

use App\Models\RetoCompletado;
use App\Models\Nivel;

class RetoCompletadoObserver
{
    /**
     * Se dispara automáticamente cuando se crea un nuevo RetoCompletado.
     * Solo actúa si el estado es 'aprobado'.
     */
    public function created(RetoCompletado $retoCompletado): void
    {
        // Solo procesar si el reto fue aprobado
        if ($retoCompletado->estado_reto_completado !== 'aprobado') {
            return;
        }

        $this->procesarXp($retoCompletado);
    }

    /**
     * Se dispara cuando se actualiza un RetoCompletado.
     */
    public function updated(RetoCompletado $retoCompletado): void
    {
        // Solo actuar si el estado cambió A 'aprobado'
        $estadoCambio = $retoCompletado->wasChanged('estado_reto_completado');
        $ahoraAprobado = $retoCompletado->estado_reto_completado === 'aprobado';

        if ($estadoCambio && $ahoraAprobado) {
            $this->procesarXp($retoCompletado);
        }
    }

    /**
     * Lógica central: suma XP, recalcula nivel, incrementa contador.
     */
    private function procesarXp(RetoCompletado $retoCompletado): void
    {
        $usuario = $retoCompletado->usuario;

        if (!$usuario) return;

        // ── 1. Sumar XP al usuario ─────────────────────────────────
        $nuevoXp = $usuario->xp_total_usuario + $retoCompletado->xp_ganado_reto_completado;

        // ── 2. Calcular el nuevo nivel ─────────────────────────────
        // Busca el nivel más alto que el usuario puede alcanzar con su XP
        $nuevoNivel = Nivel::where('xp_requerido_nivel', '<=', $nuevoXp)
            ->orderByDesc('xp_requerido_nivel')
            ->first();

        // ── 3. Actualizar usuario ──────────────────────────────────
        $usuario->update([
            'xp_total_usuario' => $nuevoXp,
            'nivel_id'         => $nuevoNivel?->id ?? $usuario->nivel_id,
        ]);

        // ── 4. Incrementar contador en el reto ────────────────────
        $retoCompletado->reto()->increment('veces_completado_reto');

        // ── 5. Verificar si subió de nivel y notificar ────────────
        if ($nuevoNivel && $nuevoNivel->id !== $usuario->getOriginal('nivel_id')) {
            $this->notificarSubidaNivel($usuario, $nuevoNivel);
        }
    }

    /**
     * Crea una notificación cuando el usuario sube de nivel.
     */
    private function notificarSubidaNivel($usuario, $nuevoNivel): void
    {
        $usuario->notificaciones()->create([
            'tipo_notificacion'  => 'nivel_subido',
            'titulo_notificacion' => '¡Subiste de nivel!',
            'cuerpo_notificacion' => '¡Felicitaciones! Ahora eres ' . $nuevoNivel->nombre_nivel,
            'entidad_tipo_notificacion' => 'nivel',
            'entidad_id_notificacion'   => $nuevoNivel->id,
        ]);
    }
}