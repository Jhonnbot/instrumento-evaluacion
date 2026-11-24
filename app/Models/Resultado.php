<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resultado extends Model
{
    protected $table = 'resultados';

    protected $primaryKey = 'id_resultado';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fecha_calificacion' => 'datetime'];
    }

    public function intento(): BelongsTo
    {
        return $this->belongsTo(Intento::class, 'id_intento', 'id_intento');
    }
}
