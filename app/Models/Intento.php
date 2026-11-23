<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Intento extends Model
{
    protected $table = 'intentos';
    protected $primaryKey = 'id_intento';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class, 'id_examen', 'id_examen');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(PreguntaIntento::class, 'id_intento', 'id_intento');
    }

    public function resultado(): HasOne
    {
        return $this->hasOne(Resultado::class, 'id_intento', 'id_intento');
    }
}
