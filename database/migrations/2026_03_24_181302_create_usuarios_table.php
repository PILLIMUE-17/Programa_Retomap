<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_usuario', 100);
            $table->string('email_usuario', 150)->unique();
            $table->string('username_usuario', 50)->unique();
            $table->text('password_hash_usuario');
            $table->text('avatar_url_usuario')->nullable();
            $table->unsignedInteger('xp_total_usuario')->default(0);
            $table->unsignedSmallInteger('racha_dias_usuario')->default(0);
            $table->foreignId('nivel_id')                          // FK a niveles (BIGINT)
                  ->default(1)
                  ->constrained('niveles')
                  ->restrictOnDelete();
            $table->string('ciudad_usuario', 100)->nullable();
            $table->boolean('activo_usuario')->default(true);
            $table->timestamp('ultimo_acceso_usuario')->nullable();
            $table->timestamps();                                   // created_at, updated_at
            $table->softDeletes();                                  // deleted_at — baja lógica segura

            $table->index('xp_total_usuario', 'idx_usuario_xp');
            $table->index('username_usuario', 'idx_usuario_username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};