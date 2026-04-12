<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios', function (Blueprint $table) {
            $table->id();
            $table->char('publicacion_id', 36);
            $table->char('usuario_id', 36);
            $table->text('contenido_comentario');
            $table->boolean('visible_comentario')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('publicacion_id', 'idx_comentario_publicacion');
            $table->index('created_at', 'idx_comentario_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios');
    }
};
