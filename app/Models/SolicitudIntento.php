<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** Solicitud de una oportunidad extra, que autoriza como máximo un intento. */
class SolicitudIntento extends Model
{
    protected $table = 'solicitudes_intento';
    protected $primaryKey = 'id_solicitud';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario', 'id_coordinador', 'fecha_solicitud',
        'estado', 'fecha_resolucion', 'observaciones',
    ];

    protected function casts(): array
    {
        return ['fecha_solicitud' => 'datetime', 'fecha_resolucion' => 'datetime'];
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_coordinador', 'id_usuario');
    }

    public function intento(): HasOne
    {
        return $this->hasOne(Intento::class, 'id_solicitud', 'id_solicitud');
    }
}
