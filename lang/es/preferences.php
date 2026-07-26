<?php

return [
    'title' => 'Preferencias',
    'subtitle' => 'Idioma, tema y color de acento de la interfaz.',

    'language' => [
        'title' => 'Idioma',
        'description' => 'Selecciona el idioma de la interfaz.',
        'placeholder' => 'Selecciona el idioma',
    ],

    'theme' => [
        'title' => 'Tema',
        'description' => 'Selecciona el tema de la interfaz.',
    ],

    'color' => [
        'title' => 'Color',
        'description' => 'Selecciona el color principal aplicado a los detalles de la interfaz.',
    ],

    'sound' => [
        'title' => 'Sonido',
        'description' => 'Reproduce un sonido breve cuando una transacción se marca como pagada.',
        'aria_label' => 'Retroalimentación sonora',
    ],

    'sound_type' => [
        'title' => 'Tipo de Sonido',
        'description' => 'Elige el sonido y escucha una vista previa.',
        'placeholder' => 'Selecciona un sonido',
    ],

    'notifications' => [
        'title' => 'Notificaciones',
        'description' => 'Recibe un correo cuando una transacción vence hoy o está por vencer.',
        'aria_label' => 'Notificaciones',
    ],

    'reminder' => [
        'title' => 'Anticipación del Recordatorio',
        'description' => 'Siempre notificamos en la fecha de vencimiento, además de esta cantidad de días antes.',
        'placeholder' => 'Selecciona',
    ],

    'days_before' => [
        'n1' => '1 día antes',
        'n3' => '3 días antes',
        'n5' => '5 días antes',
        'n7' => '7 días antes',
        'n10' => '10 días antes',
    ],
];
