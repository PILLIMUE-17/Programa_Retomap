<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_retos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_tipo_reto', 50)->unique();
            $table->string('icono_tipo_reto', 10);
            $table->text('descripcion_tipo_reto')->nullable();       // ★ NUEVO
            $table->timestamps();
        });

        DB::table('tipo_retos')->insert([
            ['nombre_tipo_reto' => 'Fotografía',  'icono_tipo_reto' => '📸', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_reto' => 'Video',       'icono_tipo_reto' => '🎥', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_reto' => 'Encuesta',    'icono_tipo_reto' => '📝', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_reto' => 'Lugar',       'icono_tipo_reto' => '📍', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_reto' => 'Social',      'icono_tipo_reto' => '👥', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_reto' => 'Reto físico', 'icono_tipo_reto' => '🎯', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_tipo_reto' => 'Tiempo',      'icono_tipo_reto' => '⏱', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_retos');
    }
};
