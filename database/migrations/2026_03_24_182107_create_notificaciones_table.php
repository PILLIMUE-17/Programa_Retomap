<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->char('usuario_id', 36);
            $table->enum('tipo_notificacion', [
                'like',
                'comentario',
                'amistad_solicitud',
                'amistad_aceptada',
                'reto_nuevo',
                'insignia',
                'nivel_subido',
                'beneficio',
            ]);
            $table->string('titulo_notificacion', 150);
            $table->text('cuerpo_notificacion')->nullable();
            $table->string('entidad_tipo_notificacion', 50)->nullable();
            $table->char('entidad_id_notificacion', 36)->nullable();
            $table->boolean('leida_notificacion')->default(false);
            $table->timestamp('leida_en_notificacion')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'leida_notificacion'], 'idx_notificacion_usuario');
            $table->index('created_at', 'idx_notificacion_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};