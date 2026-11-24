<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Constancia extends Model
{
    protected $table = 'constancias';

    protected $primaryKey = 'id_constancia';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['fecha_emision' => 'datetime'];
    }

    public function resultado(): BelongsTo
    {
        return $this->belongsTo(Resultado::class, 'id_resultado', 'id_resultado');
    }
}
