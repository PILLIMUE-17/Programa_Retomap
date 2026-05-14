<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publicaciones', function (Blueprint $table) {
            $table->unsignedInteger('comentarios_cache_publicacion')
                  ->default(0)
                  ->after('likes_cache_publicacion');
            $table->unsignedInteger('compartidos_cache_publicacion')
                  ->default(0)
                  ->after('comentarios_cache_publicacion');
        });
    }

    public function down(): void
    {
        Schema::table('publicaciones', function (Blueprint $table) {
            $table->dropColumn(['comentarios_cache_publicacion', 'compartidos_cache_publicacion']);
        });
    }
};
