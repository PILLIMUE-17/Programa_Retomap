<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canjes', function (Blueprint $table) {
            $table->id();
            $table->char('usuario_id', 36);
            $table->unsignedBigInteger('beneficio_id');
            $table->unsignedInteger('xp_descontado_canje');
            $table->string('codigo_unico_canje', 20)->unique();
            $table->boolean('usado_canje')->default(false);
            $table->timestamp('usado_en_canje')->nullable();
            $table->timestamps();

            $table->index('usuario_id', 'idx_canje_usuario');
            $table->index('codigo_unico_canje', 'idx_canje_codigo');
            $table->index(['usado_canje', 'beneficio_id'], 'idx_canje_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canjes');
    }
};
