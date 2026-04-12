<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveles', function (Blueprint $table) {
            $table->id();                                            // BIGINT AUTO_INCREMENT
            $table->string('nombre_nivel', 50);
            $table->unsignedInteger('xp_requerido_nivel')->default(0);
            $table->text('insignia_url_nivel')->nullable();
            $table->text('descripcion_nivel')->nullable();
            $table->timestamps();
        });

        DB::table('niveles')->insert([
            ['nombre_nivel' => 'Principiante',      'xp_requerido_nivel' => 0,    'descripcion_nivel' => 'Recién llegado a Retomap',             'created_at' => now(), 'updated_at' => now()],
            ['nombre_nivel' => 'Explorador',         'xp_requerido_nivel' => 300,  'descripcion_nivel' => 'Has descubierto tus primeros destinos', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_nivel' => 'Viajero',            'xp_requerido_nivel' => 800,  'descripcion_nivel' => 'Conoces el territorio como pocos',      'created_at' => now(), 'updated_at' => now()],
            ['nombre_nivel' => 'Aventurero',         'xp_requerido_nivel' => 1500, 'descripcion_nivel' => 'Conquistador de retos difíciles',       'created_at' => now(), 'updated_at' => now()],
            ['nombre_nivel' => 'Guardián del Huila', 'xp_requerido_nivel' => 3000, 'descripcion_nivel' => 'Embajador del turismo huilense',        'created_at' => now(), 'updated_at' => now()],
            ['nombre_nivel' => 'Leyenda',            'xp_requerido_nivel' => 6000, 'descripcion_nivel' => 'Maestro absoluto de Retomap',           'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('niveles');
    }
};