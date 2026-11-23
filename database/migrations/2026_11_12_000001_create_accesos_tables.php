<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre', 40)->unique();
            $table->text('descripcion')->nullable();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->foreignId('id_rol')->constrained('roles', 'id_rol')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nombre_completo', 150);
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 150)->unique();
            $table->char('curp', 18)->nullable()->unique();
            $table->string('password_hash', 255);
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->nullable();
            $table->rememberToken();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_curp CHECK (curp IS NULL OR CHAR_LENGTH(curp) = 18)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('roles');
    }
};
