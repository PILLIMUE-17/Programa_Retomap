<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reto_completados', function (Blueprint $table) {
            // 1. Eliminar indices antes de cambiar tipos de columna
            $table->dropUnique('uq_usuario_reto');
            $table->dropIndex('idx_completado_usuario');
            $table->dropIndex('idx_completado_reto');

            // 2. Corregir tipos: char(36) → unsignedBigInteger
            //    para que coincidan con usuarios.id y retos.id (BIGINT)
            $table->unsignedBigInteger('usuario_id')->change();
            $table->unsignedBigInteger('reto_id')->change();

            // 3. Corregir default: 'aprobado' → 'pendiente'
            //    para que los retos pasen por revision del admin
            $table->enum('estado_reto_completado', ['pendiente', 'aprobado', 'rechazado'])
                  ->default('pendiente')
                  ->change();

            // 4. Re-agregar indices
            $table->unique(['usuario_id', 'reto_id'], 'uq_usuario_reto');
            $table->index('usuario_id', 'idx_completado_usuario');
            $table->index('reto_id', 'idx_completado_reto');
        });
    }

    public function down(): void
    {
        Schema::table('reto_completados', function (Blueprint $table) {
            $table->dropUnique('uq_usuario_reto');
            $table->dropIndex('idx_completado_usuario');
            $table->dropIndex('idx_completado_reto');

            $table->char('usuario_id', 36)->change();
            $table->char('reto_id', 36)->change();

            $table->enum('estado_reto_completado', ['pendiente', 'aprobado', 'rechazado'])
                  ->default('aprobado')
                  ->change();

            $table->unique(['usuario_id', 'reto_id'], 'uq_usuario_reto');
            $table->index('usuario_id', 'idx_completado_usuario');
            $table->index('reto_id', 'idx_completado_reto');
        });
    }
};
