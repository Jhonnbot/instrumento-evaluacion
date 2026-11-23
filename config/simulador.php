<?php
return [
 'fecha_inicio_global' => env('SIMULADOR_FECHA_INICIO') ?: null,
 'dias' => 14,
 'cantidad' => (int) env('SIMULADOR_PREGUNTAS_POR_SESION', 6),
 'por_pagina' => 2,
];
