<?php

return [
    // Cantidad provisional para desarrollo: la documentación exige cinco categorías al 20 %,
    // pero no especifica cuántos reactivos conformarán el examen definitivo.
    'preguntas_por_categoria' => max(2, (int) env('EXAMEN_PREGUNTAS_POR_CATEGORIA', 2)),
];
