<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lugares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_lugar', 150);
            $table->text('descripcion_lugar')->nullable();
            $table->decimal('latitud_lugar', 10, 7);
            $table->decimal('longitud_lugar', 10, 7);
            $table->text('direccion_lugar')->nullable();
            $table->string('municipio_lugar', 100)->default('Neiva');
            $table->string('departamento_lugar', 100)->default('Huila');
            $table->boolean('verificado_lugar')->default(false);
            $table->text('imagen_url_lugar')->nullable();
            $table->string('horario_lugar', 200)->nullable();
            $table->unsignedTinyInteger('calificacion_promedio_lugar')->default(0);
            $table->unsignedBigInteger('categoria_id')->nullable();
            // Sin FK constraint a aliados — relación manejada en el modelo Eloquent
            $table->char('aliado_id', 36)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['latitud_lugar', 'longitud_lugar'], 'idx_lugar_geo');
            $table->index('categoria_id', 'idx_lugar_categoria');
            $table->index('municipio_lugar', 'idx_lugar_municipio');
            $table->index('aliado_id', 'idx_lugar_aliado');
        });

        // Seed lugares de Neiva
        $restauranteId = DB::table('categorias')->where('nombre_categoria', 'Restaurante')->value('id');
        $parqueId      = DB::table('categorias')->where('nombre_categoria', 'Parque / Naturaleza')->value('id');
        $ccId          = DB::table('categorias')->where('nombre_categoria', 'Centro Comercial')->value('id');
        $nochId        = DB::table('categorias')->where('nombre_categoria', 'Vida Nocturna')->value('id');
        $culturaId     = DB::table('categorias')->where('nombre_categoria', 'Cultura & Arte')->value('id');

        $lugares = [
            ['Parque Central Santander',  2.9262767, -75.2892111, $parqueId,      'Parque icónico en el centro de Neiva'],
            ['Malecón Río Magdalena',     2.9270850, -75.2956620, $parqueId,      'Paseo junto al río más importante de Colombia'],
            ['El Patio Casa Cultural',    2.9259320, -75.2844090, $restauranteId, 'Gastronomía huilense en ambiente cultural'],
            ['Penelope Waffles',          2.9279347, -75.2897868, $restauranteId, 'Waffles artesanales en el corazón de Neiva'],
            ['Dinastía Restaurante',      2.9304031, -75.2865497, $restauranteId, 'Restaurante gourmet con escalera icónica'],
            ['San Pedro Plaza',           2.9507459, -75.2884085, $ccId,          'Centro comercial moderno al norte de Neiva'],
            ['UNICO Outlet Neiva',        2.9618835, -75.2933920, $ccId,          'Outlet con grandes descuentos'],
            ['Tulum Neiva',               2.9305157, -75.2841344, $nochId,        'Bar lounge de ambiente caribeño'],
            ['Mayté Discoteca',           2.9317115, -75.2912643, $nochId,        'Discoteca con pista animada'],
            ['Museo Arte Contemporáneo',  2.9372925, -75.2933916, $culturaId,     'Arte contemporáneo del Huila'],
            ['Island Park Mohán',         2.9258330, -75.2939200, $parqueId,      'Parque temático con fauna local'],
        ];

        foreach ($lugares as [$nombre, $lat, $lng, $catId, $desc]) {
            DB::table('lugares')->insert([
                'nombre_lugar'      => $nombre,
                'latitud_lugar'     => $lat,
                'longitud_lugar'    => $lng,
                'municipio_lugar'   => 'Neiva',
                'verificado_lugar'  => true,
                'descripcion_lugar' => $desc,
                'categoria_id'      => $catId,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares');
    }
};