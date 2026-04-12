<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicaciones', function (Blueprint $table) {
            $table->id();
            $table->char('usuario_id', 36);
            $table->unsignedBigInteger('reto_completado_id')->nullable();
            $table->text('caption_publicacion')->nullable();
            $table->text('imagen_url_publicacion')->nullable();
            $table->unsignedInteger('likes_cache_publicacion')->default(0);
            $table->boolean('visible_publicacion')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('usuario_id', 'idx_publicacion_usuario');
            $table->index('created_at', 'idx_publicacion_feed');
            $table->index('visible_publicacion', 'idx_publicacion_visible');
        });

        Schema::create('like_publicaciones', function (Blueprint $table) {
            $table->char('usuario_id', 36);
            $table->char('publicacion_id', 36);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['usuario_id', 'publicacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('like_publicaciones');
        Schema::dropIfExists('publicaciones');
    }
};