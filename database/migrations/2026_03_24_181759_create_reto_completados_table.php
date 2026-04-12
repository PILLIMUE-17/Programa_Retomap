<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reto_completados', function (Blueprint $table) {
            $table->id();
            $table->char('usuario_id', 36);
            $table->char('reto_id', 36);
            $table->text('evidencia_url_reto_completado')->nullable();
            $table->unsignedSmallInteger('xp_ganado_reto_completado')->default(0);
            $table->enum('estado_reto_completado', ['pendiente', 'aprobado', 'rechazado'])
                  ->default('aprobado');
            $table->text('motivo_rechazo_reto_completado')->nullable();
            $table->timestamps();

            $table->unique(['usuario_id', 'reto_id'], 'uq_usuario_reto');
            $table->index('usuario_id', 'idx_completado_usuario');
            $table->index('reto_id', 'idx_completado_reto');
            $table->index('created_at', 'idx_completado_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reto_completados');
    }
};