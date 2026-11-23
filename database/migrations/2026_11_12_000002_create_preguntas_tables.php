<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->string('nombre', 120)->unique();
            $table->decimal('ponderacion', 5, 4)->default(0.2000);
            $table->boolean('estado')->default(true);
        });

        Schema::create('temas', function (Blueprint $table) {
            $table->id('id_tema');
            $table->foreignId('id_categoria')->constrained('categorias', 'id_categoria')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->unique(['id_categoria', 'nombre'], 'uk_temas_categoria_nombre');
        });

        Schema::create('preguntas', function (Blueprint $table) {
            $table->id('id_pregunta');
            $table->foreignId('id_tema')->constrained('temas', 'id_tema')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('enunciado');
            $table->enum('tipo', ['opcion_multiple', 'seleccion_multiple', 'verdadero_falso', 'ordenamiento']);
            $table->boolean('estado')->default(true);
        });

        Schema::create('opciones_respuesta', function (Blueprint $table) {
            $table->id('id_opcion');
            $table->foreignId('id_pregunta')->constrained('preguntas', 'id_pregunta')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('texto_opcion');
            $table->boolean('es_correcta')->default(false);
            $table->integer('orden_correcto')->nullable();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE categorias ADD CONSTRAINT chk_categorias_ponderacion CHECK (ponderacion BETWEEN 0 AND 1)');
            DB::statement('ALTER TABLE opciones_respuesta ADD CONSTRAINT chk_opciones_orden CHECK (orden_correcto IS NULL OR orden_correcto > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_respuesta');
        Schema::dropIfExists('preguntas');
        Schema::dropIfExists('temas');
        Schema::dropIfExists('categorias');
    }
};
