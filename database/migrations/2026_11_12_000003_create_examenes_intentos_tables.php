<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examenes', function (Blueprint $table) {
            $table->id('id_examen');
            $table->string('nombre', 150);
            $table->integer('duracion_minutos')->default(50);
            $table->integer('minimo_aprobatorio')->default(700);
            $table->integer('preguntas_por_pantalla')->default(2);
            $table->boolean('activo')->default(true);
        });

        Schema::create('solicitudes_intento', function (Blueprint $table) {
            $table->id('id_solicitud');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_coordinador')->nullable()->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('fecha_solicitud')->useCurrent();
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada'])->default('pendiente');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->text('observaciones')->nullable();
        });

        Schema::create('intentos', function (Blueprint $table) {
            $table->id('id_intento');
            $table->foreignId('id_usuario')->constrained('usuarios', 'id_usuario')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_examen')->constrained('examenes', 'id_examen')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_solicitud')->nullable()->unique('uk_intentos_solicitud')->constrained('solicitudes_intento', 'id_solicitud')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('numero_intento');
            $table->dateTime('fecha_inicio')->useCurrent();
            $table->dateTime('fecha_fin')->nullable();
            $table->enum('estado', ['en_curso', 'enviado', 'tiempo_agotado'])->default('en_curso');
            $table->unique(['id_usuario', 'id_examen', 'numero_intento'], 'uk_intentos_numero');
        });

        Schema::create('preguntas_intento', function (Blueprint $table) {
            $table->id('id_intento_pregunta');
            $table->foreignId('id_intento')->constrained('intentos', 'id_intento')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_pregunta')->constrained('preguntas', 'id_pregunta')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('orden_presentacion');
            $table->unique(['id_intento', 'id_pregunta'], 'uk_preguntas_intento');
            $table->unique(['id_intento', 'orden_presentacion'], 'uk_preguntas_intento_orden');
        });

        Schema::create('respuestas_intento', function (Blueprint $table) {
            $table->id('id_respuesta');
            $table->foreignId('id_intento_pregunta')->unique('uk_respuesta_pregunta')->constrained('preguntas_intento', 'id_intento_pregunta')->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('fecha_respuesta')->nullable();
            $table->boolean('es_correcta')->nullable();
        });

        Schema::create('respuestas_opciones', function (Blueprint $table) {
            $table->foreignId('id_respuesta')->constrained('respuestas_intento', 'id_respuesta')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_opcion')->constrained('opciones_respuesta', 'id_opcion')->cascadeOnUpdate()->restrictOnDelete();
            $table->integer('orden_seleccion')->nullable();
            $table->primary(['id_respuesta', 'id_opcion']);
            $table->unique(['id_respuesta', 'orden_seleccion'], 'uk_respuestas_orden');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE examenes ADD CONSTRAINT chk_examen_duracion CHECK (duracion_minutos BETWEEN 1 AND 50)');
            DB::statement('ALTER TABLE examenes ADD CONSTRAINT chk_examen_aprobacion CHECK (minimo_aprobatorio BETWEEN 0 AND 1000)');
            DB::statement('ALTER TABLE examenes ADD CONSTRAINT chk_examen_pantalla CHECK (preguntas_por_pantalla > 0)');
            DB::statement('ALTER TABLE intentos ADD CONSTRAINT chk_intentos_numero CHECK (numero_intento >= 1)');
            DB::statement('ALTER TABLE intentos ADD CONSTRAINT chk_intentos_fechas CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)');
            DB::statement('ALTER TABLE preguntas_intento ADD CONSTRAINT chk_preguntas_orden CHECK (orden_presentacion > 0)');
            DB::statement('ALTER TABLE respuestas_opciones ADD CONSTRAINT chk_respuestas_orden CHECK (orden_seleccion IS NULL OR orden_seleccion > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_opciones');
        Schema::dropIfExists('respuestas_intento');
        Schema::dropIfExists('preguntas_intento');
        Schema::dropIfExists('intentos');
        Schema::dropIfExists('solicitudes_intento');
        Schema::dropIfExists('examenes');
    }
};
