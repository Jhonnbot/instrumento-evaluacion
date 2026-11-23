<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/** Solo presentación: las fechas oficiales continúan almacenándose en UTC. */
final class FechaMexico
{
    public const ZONA = 'America/Mexico_City';

    public static function mostrar(DateTimeInterface|string|null $fecha, string $formato = 'd/m/Y H:i'): string
    {
        if ($fecha === null || $fecha === '') {
            return '—';
        }

        $momento = $fecha instanceof DateTimeInterface
            ? CarbonImmutable::instance($fecha)
            : CarbonImmutable::parse($fecha, 'UTC');

        return $momento->setTimezone(self::ZONA)->format($formato);
    }

    /** Inicio inclusivo del día local, expresado en UTC para consultar DATETIME. */
    public static function inicioDiaUtc(string $fecha): string
    {
        return CarbonImmutable::parse($fecha, self::ZONA)
            ->startOfDay()->utc()->toDateTimeString();
    }

    /** Inicio exclusivo del siguiente día local, expresado en UTC. */
    public static function finDiaExclusivoUtc(string $fecha): string
    {
        return CarbonImmutable::parse($fecha, self::ZONA)
            ->addDay()->startOfDay()->utc()->toDateTimeString();
    }
}
