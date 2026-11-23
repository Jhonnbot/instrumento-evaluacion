<?php

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Schedule;
use App\Services\Evaluaciones\ExamenService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alta del coordinador SOLO desde la consola, nunca desde un formulario público.
Artisan::command('cocosoft:crear-coordinador', function (): void {
    $nombre = trim((string) $this->ask('Nombre completo del coordinador'));
    $correo = mb_strtolower(trim((string) $this->ask('Correo del coordinador')));
    $clave = (string) $this->secret('Contraseña segura (mínimo 12 caracteres, letras, número y símbolo)');

    $validator = Validator::make(
        ['nombre_completo' => $nombre, 'correo' => $correo, 'password' => $clave],
        [
            'nombre_completo' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'email', 'max:150', 'unique:usuarios,correo'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ],
    );

    if ($validator->fails()) {
        $this->error($validator->errors()->first());
        return;
    }

    $rol = Rol::query()->where('nombre', 'Coordinador')->firstOrFail();

    $user = User::query()->create([
        'id_rol' => $rol->getKey(),
        'nombre_completo' => $nombre,
        'correo' => $correo,
        'password_hash' => Hash::make($clave),
        'estado' => 'activo',
    ]);

    $this->info('Coordinador creado. Identificador: '.$user->getKey());
})->purpose('Crear una cuenta de coordinador de forma administrativa y segura');

// El navegador puede cerrarse durante el examen; el servidor cierra intentos vencidos.
Artisan::command('cocosoft:cerrar-examenes', function (ExamenService $servicio): void {
    $this->info('Intentos vencidos cerrados: '.$servicio->cerrarVencidos());
})->purpose('Finalizar intentos oficiales vencidos, guardar calificaciones e historial');

Schedule::command('cocosoft:cerrar-examenes')->everyMinute()->withoutOverlapping();
