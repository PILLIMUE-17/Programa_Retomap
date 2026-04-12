<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insignias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_insignia', 100)->unique();
            $table->text('descripcion_insignia')->nullable();
            $table->string('icono_insignia', 10)->nullable();
            $table->text('condicion_insignia')->nullable();
            $table->string('categoria_insignia', 50)->nullable();
            $table->timestamps();
        });

        DB::table('insignias')->insert([
            ['nombre_insignia' => 'Primer Reto',   'descripcion_insignia' => 'Completaste tu primer reto',           'icono_insignia' => '🚀', 'condicion_insignia' => 'Completar 1 reto',               'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Fotógrafo',     'descripcion_insignia' => '10 retos de fotografía completados',   'icono_insignia' => '📸', 'condicion_insignia' => '10 retos tipo Fotografía',      'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Explorador',    'descripcion_insignia' => '5 municipios distintos visitados',     'icono_insignia' => '🗺',  'condicion_insignia' => 'Retos en 5 municipios',         'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Veloz',         'descripcion_insignia' => 'Completa un reto en menos de 10 min', 'icono_insignia' => '⚡',  'condicion_insignia' => 'Tiempo de completado < 10 min', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Río Magdalena', 'descripcion_insignia' => 'Visita el malecón de Neiva',          'icono_insignia' => '🌊', 'condicion_insignia' => 'Completar reto en Malecón',     'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Top 50',        'descripcion_insignia' => 'Llegar al top 50 del ranking',        'icono_insignia' => '🏆', 'condicion_insignia' => 'Posición ≤ 50 en ranking',      'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Social',        'descripcion_insignia' => '5 amigos agregados',                  'icono_insignia' => '👫', 'condicion_insignia' => '5 amistades aceptadas',         'created_at' => now(), 'updated_at' => now()],
            ['nombre_insignia' => 'Gastronómico',  'descripcion_insignia' => '10 retos en restaurantes',            'icono_insignia' => '🍽',  'condicion_insignia' => '10 retos en categoría Restaurante', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('usuario_insignias', function (Blueprint $table) {
            $table->char('usuario_id', 36);
            $table->unsignedBigInteger('insignia_id');
            $table->timestamp('obtenida_en_usuario_insignia')->useCurrent();

            $table->primary(['usuario_id', 'insignia_id']);
            $table->index('usuario_id', 'idx_ui_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_insignias');
        Schema::dropIfExists('insignias');
    }
};
