<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── beneficios: aliado_id char(36) → unsignedBigInteger ──────
        Schema::table('beneficios', function (Blueprint $table) {
            $table->dropIndex('idx_beneficio_aliado');
            $table->unsignedBigInteger('aliado_id')->change();
            $table->index('aliado_id', 'idx_beneficio_aliado');
        });

        // ── canjes: usuario_id char(36) → unsignedBigInteger ─────────
        Schema::table('canjes', function (Blueprint $table) {
            $table->dropIndex('idx_canje_usuario');
            $table->unsignedBigInteger('usuario_id')->change();
            $table->index('usuario_id', 'idx_canje_usuario');
        });

        // ── amistades: solicitante_id y receptor_id ───────────────────
        Schema::table('amistades', function (Blueprint $table) {
            $table->dropUnique('uq_amistad');
            $table->dropIndex('idx_amistad_receptor');
            $table->unsignedBigInteger('solicitante_id')->change();
            $table->unsignedBigInteger('receptor_id')->change();
            $table->unique(['solicitante_id', 'receptor_id'], 'uq_amistad');
            $table->index('receptor_id', 'idx_amistad_receptor');
        });

        // ── publicaciones: usuario_id char(36) → unsignedBigInteger ──
        Schema::table('publicaciones', function (Blueprint $table) {
            $table->dropIndex('idx_publicacion_usuario');
            $table->unsignedBigInteger('usuario_id')->change();
            $table->index('usuario_id', 'idx_publicacion_usuario');
        });

        // ── like_publicaciones: pk compuesta, ambos char(36) ─────────
        Schema::table('like_publicaciones', function (Blueprint $table) {
            $table->dropPrimary();
            $table->unsignedBigInteger('usuario_id')->change();
            $table->unsignedBigInteger('publicacion_id')->change();
            $table->primary(['usuario_id', 'publicacion_id']);
        });

        // ── notificaciones: usuario_id char(36) → unsignedBigInteger ─
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_notificacion_usuario');
            $table->unsignedBigInteger('usuario_id')->change();
            $table->unsignedBigInteger('entidad_id_notificacion')->nullable()->change();
            $table->index(['usuario_id', 'leida_notificacion'], 'idx_notificacion_usuario');
        });

        // ── comentarios: publicacion_id y usuario_id char(36) ────────
        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropIndex('idx_comentario_publicacion');
            $table->unsignedBigInteger('publicacion_id')->change();
            $table->unsignedBigInteger('usuario_id')->change();
            $table->index('publicacion_id', 'idx_comentario_publicacion');
        });

        // ── usuario_insignias: usuario_id char(36) → unsignedBigInteger
        Schema::table('usuario_insignias', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('idx_ui_usuario');
            $table->unsignedBigInteger('usuario_id')->change();
            $table->primary(['usuario_id', 'insignia_id']);
            $table->index('usuario_id', 'idx_ui_usuario');
        });
    }

    public function down(): void
    {
        Schema::table('beneficios', function (Blueprint $table) {
            $table->dropIndex('idx_beneficio_aliado');
            $table->char('aliado_id', 36)->change();
            $table->index('aliado_id', 'idx_beneficio_aliado');
        });

        Schema::table('canjes', function (Blueprint $table) {
            $table->dropIndex('idx_canje_usuario');
            $table->char('usuario_id', 36)->change();
            $table->index('usuario_id', 'idx_canje_usuario');
        });

        Schema::table('amistades', function (Blueprint $table) {
            $table->dropUnique('uq_amistad');
            $table->dropIndex('idx_amistad_receptor');
            $table->char('solicitante_id', 36)->change();
            $table->char('receptor_id', 36)->change();
            $table->unique(['solicitante_id', 'receptor_id'], 'uq_amistad');
            $table->index('receptor_id', 'idx_amistad_receptor');
        });

        Schema::table('publicaciones', function (Blueprint $table) {
            $table->dropIndex('idx_publicacion_usuario');
            $table->char('usuario_id', 36)->change();
            $table->index('usuario_id', 'idx_publicacion_usuario');
        });

        Schema::table('like_publicaciones', function (Blueprint $table) {
            $table->dropPrimary();
            $table->char('usuario_id', 36)->change();
            $table->char('publicacion_id', 36)->change();
            $table->primary(['usuario_id', 'publicacion_id']);
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex('idx_notificacion_usuario');
            $table->char('usuario_id', 36)->change();
            $table->char('entidad_id_notificacion', 36)->nullable()->change();
            $table->index(['usuario_id', 'leida_notificacion'], 'idx_notificacion_usuario');
        });

        Schema::table('comentarios', function (Blueprint $table) {
            $table->dropIndex('idx_comentario_publicacion');
            $table->char('publicacion_id', 36)->change();
            $table->char('usuario_id', 36)->change();
            $table->index('publicacion_id', 'idx_comentario_publicacion');
        });

        Schema::table('usuario_insignias', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropIndex('idx_ui_usuario');
            $table->char('usuario_id', 36)->change();
            $table->primary(['usuario_id', 'insignia_id']);
            $table->index('usuario_id', 'idx_ui_usuario');
        });
    }
};
