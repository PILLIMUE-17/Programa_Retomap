<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amistades', function (Blueprint $table) {
            $table->id();
            $table->char('solicitante_id', 36);
            $table->char('receptor_id', 36);
            $table->enum('estado_amistad', ['pendiente', 'aceptada', 'bloqueada'])
                  ->default('pendiente');
            $table->timestamps();

            $table->unique(['solicitante_id', 'receptor_id'], 'uq_amistad');
            $table->index('receptor_id', 'idx_amistad_receptor');
            $table->index('estado_amistad', 'idx_amistad_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amistades');
    }
};