<?php

namespace Database\Factories;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id_rol' => fn (): int => (int) Rol::query()->firstOrCreate(
                ['nombre' => 'Alumno'],
                ['descripcion' => 'Usuario que realiza simuladores y examenes'],
            )->getKey(),
            'nombre_completo' => fake()->name(),
            'telefono' => fake()->numerify('##########'),
            'correo' => fake()->unique()->safeEmail(),
            'curp' => null,
            'password_hash' => Hash::make('ClaveDePruebas2026!'),
            'estado' => 'activo',
        ];
    }

    public function coordinador(): static
    {
        return $this->state(fn (): array => [
            'id_rol' => (int) Rol::query()->firstOrCreate(
                ['nombre' => 'Coordinador'],
                ['descripcion' => 'Usuario que administra alumnos, preguntas y evaluaciones'],
            )->getKey(),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(fn (): array => ['estado' => 'inactivo']);
    }
}
