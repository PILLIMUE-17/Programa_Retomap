<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();                                            // BIGINT — tabla de catálogo interna
            $table->string('nombre_categoria', 50)->unique();
            $table->string('icono_categoria', 10);
            $table->string('color_hex_categoria', 7);
            $table->timestamps();
        });

        DB::table('categorias')->insert([
            ['nombre_categoria' => 'Restaurante',         'icono_categoria' => '🍽',  'color_hex_categoria' => '#FF9A5C', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Parque / Naturaleza', 'icono_categoria' => '🌿',  'color_hex_categoria' => '#3DCB6B', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Centro Comercial',    'icono_categoria' => '🛍',  'color_hex_categoria' => '#4DAAFF', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Vida Nocturna',       'icono_categoria' => '🌙',  'color_hex_categoria' => '#9B59FF', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Cultura & Arte',      'icono_categoria' => '🎨',  'color_hex_categoria' => '#FFD93D', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Sitio Histórico',     'icono_categoria' => '🏛',  'color_hex_categoria' => '#C0A060', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Aventura',            'icono_categoria' => '🧗',  'color_hex_categoria' => '#FF4D88', 'created_at' => now(), 'updated_at' => now()],
            ['nombre_categoria' => 'Gastronomía Local',   'icono_categoria' => '🫕',  'color_hex_categoria' => '#E87040', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};