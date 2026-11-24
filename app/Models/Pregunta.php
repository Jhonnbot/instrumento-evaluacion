<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pregunta extends Model
{
    protected $table = 'preguntas';

    protected $primaryKey = 'id_pregunta';

    public $timestamps = false;

    public const TIPOS = [
        'opcion_multiple' => 'Opción múltiple (una correcta)',
        'seleccion_multiple' => 'Selección múltiple (varias correctas)',
        'verdadero_falso' => 'Verdadero / falso',
        'ordenamiento' => 'Ordenamiento en texto',
    ];

    protected $fillable = ['id_tema', 'enunciado', 'tipo', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function tema(): BelongsTo
    {
        return $this->belongsTo(Tema::class, 'id_tema', 'id_tema');
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(OpcionRespuesta::class, 'id_pregunta', 'id_pregunta');
    }

    public function preguntasIntento(): HasMany
    {
        return $this->hasMany(PreguntaIntento::class, 'id_pregunta', 'id_pregunta');
    }
}
