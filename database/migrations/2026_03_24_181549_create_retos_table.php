<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_reto', 150);
            $table->text('descripcion_reto');
            $table->unsignedSmallInteger('xp_recompensa_reto')->default(50);
            $table->text('instruccion_evidencia_reto')->nullable();
            $table->timestamp('expira_en_reto')->nullable();
            $table->boolean('activo_reto')->default(true);
            $table->unsignedInteger('veces_completado_reto')->default(0);
            $table->unsignedTinyInteger('dificultad_reto')->default(1);
            $table->unsignedBigInteger('lugar_id');         // ← BIGINT igual que lugares.id
            $table->foreignId('tipo_reto_id')
                  ->nullable()
                  ->constrained('tipo_retos')
                  ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('lugar_id', 'idx_reto_lugar');
            $table->index('xp_recompensa_reto', 'idx_reto_xp');
            $table->index('activo_reto', 'idx_reto_activo');
            // FK directo con constrained ya que ambos son BIGINT
            $table->foreign('lugar_id')
                  ->references('id')
                  ->on('lugares')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retos');
    }
};