<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Modelo mínimo para detectar reactivos ya asignados a exámenes. */
class PreguntaIntento extends Model
{
    protected $table = 'preguntas_intento';
    protected $primaryKey = 'id_intento_pregunta';
    public $timestamps = false;

    protected $guarded = ['*'];
}
