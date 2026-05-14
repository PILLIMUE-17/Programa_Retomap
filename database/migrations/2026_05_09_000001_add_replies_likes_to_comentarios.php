<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comentarios', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')
                  ->nullable()
                  ->after('publicacion_id');
            $table->unsignedInteger('likes_cache_comentario')
                  ->default(0)
                  ->after('contenido_comentario');
        });

        Schema::create('like_comentarios', function (Blueprint $table) {
            $table->char('usuario_id', 36);
            $table->unsignedBigInteger('comentario_id');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['usuario_id', 'comentario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('like_comentarios');
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'likes_cache_comentario']);
        });
    }
};
