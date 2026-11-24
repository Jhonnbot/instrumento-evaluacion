<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tema extends Model
{
    protected $table = 'temas';

    protected $primaryKey = 'id_tema';

    public $timestamps = false;

    protected $fillable = ['id_categoria', 'nombre', 'descripcion'];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class, 'id_tema', 'id_tema');
    }
}
