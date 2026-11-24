<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    protected $table = 'examenes';

    protected $primaryKey = 'id_examen';

    public $timestamps = false;

    protected $fillable = ['nombre', 'duracion_minutos', 'minimo_aprobatorio', 'preguntas_por_pantalla', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'duracion_minutos' => 'integer',
            'minimo_aprobatorio' => 'integer', 'preguntas_por_pantalla' => 'integer'];
    }

    public function intentos(): HasMany
    {
        return $this->hasMany(Intento::class, 'id_examen', 'id_examen');
    }
}
