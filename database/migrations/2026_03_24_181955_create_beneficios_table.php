<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficios', function (Blueprint $table) {
            $table->id();
            $table->char('aliado_id', 36);
            $table->text('descripcion_beneficio');
            $table->unsignedInteger('costo_xp_beneficio');
            $table->date('valido_hasta_beneficio')->nullable();
            $table->unsignedInteger('cantidad_disponible_beneficio')->nullable();
            $table->unsignedInteger('cantidad_canjeada_beneficio')->default(0);
            $table->boolean('activo_beneficio')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('aliado_id', 'idx_beneficio_aliado');
            $table->index(['activo_beneficio', 'valido_hasta_beneficio'], 'idx_beneficio_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficios');
    }
};