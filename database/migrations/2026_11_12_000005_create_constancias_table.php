<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('constancias', function (Blueprint $table) {
            $table->id('id_constancia');
            $table->foreignId('id_resultado')->unique()->constrained('resultados', 'id_resultado')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('folio', 64)->unique();
            $table->string('clave_verificacion', 128)->unique();
            $table->dateTime('fecha_emision')->useCurrent();
            $table->string('ruta_pdf', 255);
            $table->enum('estado', ['vigente', 'revocada'])->default('vigente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('constancias');
    }
};
