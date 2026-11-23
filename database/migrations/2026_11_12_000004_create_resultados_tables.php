<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados', function (Blueprint $table) {
            $table->id('id_resultado');
            $table->foreignId('id_intento')->unique()->constrained('intentos', 'id_intento')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('calificacion_final');
            $table->enum('dictamen', ['APROBADO', 'REPROBADO']);
            $table->dateTime('fecha_calificacion')->useCurrent();
        });

        Schema::create('detalle_resultado_categoria', function (Blueprint $table) {
            $table->id('id_detalle_categoria');
            $table->foreignId('id_resultado')->constrained('resultados', 'id_resultado')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_categoria')->constrained('categorias', 'id_categoria')->cascadeOnUpdate()->restrictOnDelete();
            $table->decimal('puntaje_obtenido', 7, 2);
            $table->decimal('ponderacion_aplicada', 5, 4)->default(0.2000);
            $table->unique(['id_resultado', 'id_categoria'], 'uk_detalle_categoria');
        });

        Schema::create('detalle_resultado_tema', function (Blueprint $table) {
            $table->id('id_detalle_tema');
            $table->foreignId('id_resultado')->constrained('resultados', 'id_resultado')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_tema')->constrained('temas', 'id_tema')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('preguntas_totales')->default(0);
            $table->integer('respuestas_correctas')->default(0);
            $table->unique(['id_resultado', 'id_tema'], 'uk_detalle_tema');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE resultados ADD CONSTRAINT chk_resultados_calificacion CHECK (calificacion_final BETWEEN 0 AND 1000)');
            DB::statement("ALTER TABLE resultados ADD CONSTRAINT chk_resultados_dictamen CHECK ((calificacion_final >= 700 AND dictamen = 'APROBADO') OR (calificacion_final < 700 AND dictamen = 'REPROBADO'))");
            DB::statement('ALTER TABLE detalle_resultado_categoria ADD CONSTRAINT chk_detalle_categoria_puntaje CHECK (puntaje_obtenido BETWEEN 0 AND 200)');
            DB::statement('ALTER TABLE detalle_resultado_categoria ADD CONSTRAINT chk_detalle_categoria_ponderacion CHECK (ponderacion_aplicada BETWEEN 0 AND 1)');
            DB::statement('ALTER TABLE detalle_resultado_tema ADD CONSTRAINT chk_detalle_tema_preguntas CHECK (preguntas_totales >= 0 AND respuestas_correctas >= 0 AND respuestas_correctas <= preguntas_totales)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_resultado_tema');
        Schema::dropIfExists('detalle_resultado_categoria');
        Schema::dropIfExists('resultados');
    }
};
