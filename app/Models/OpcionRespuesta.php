<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpcionRespuesta extends Model
{
    protected $table = 'opciones_respuesta';

    protected $primaryKey = 'id_opcion';

    public $timestamps = false;

    protected $fillable = ['texto_opcion', 'es_correcta', 'orden_correcto'];

    protected function casts(): array
    {
        return ['es_correcta' => 'boolean', 'orden_correcto' => 'integer'];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'id_pregunta', 'id_pregunta');
    }
}
