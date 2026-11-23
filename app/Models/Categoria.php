<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;

    protected $fillable = ['nombre', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean', 'ponderacion' => 'decimal:4'];
    }

    public function temas(): HasMany
    {
        return $this->hasMany(Tema::class, 'id_categoria', 'id_categoria');
    }
}
