<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aliados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_negocio_aliado', 150);
            $table->string('tipo_negocio_aliado', 100)->nullable();  // Ej: "Restaurante", "Hotel"
            $table->text('descripcion_aliado')->nullable();
            $table->string('contacto_email_aliado', 150)->nullable();
            $table->string('contacto_telefono_aliado', 20)->nullable();
            $table->text('logo_url_aliado')->nullable();
            $table->text('sitio_web_aliado')->nullable();
            $table->string('municipio_aliado', 100)->default('Neiva');
            $table->boolean('activo_aliado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('activo_aliado', 'idx_aliado_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aliados');
    }
};
