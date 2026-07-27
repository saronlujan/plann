<?php

return [
    'title' => 'Preferências',
    'subtitle' => 'Idioma, tema e cor de destaque da interface.',

    'language' => [
        'title' => 'Idioma',
        'description' => 'Selecione o idioma da interface.',
        'placeholder' => 'Selecione o idioma',
    ],

    'theme' => [
        'title' => 'Tema',
        'description' => 'Selecione o tema da interface.',
    ],

    'color' => [
        'title' => 'Cor',
        'description' => 'Selecione a cor principal aplicada aos detalhes da interface.',
    ],

    'sound' => [
        'title' => 'Som',
        'description' => 'Toca um som curto quando uma transação é marcada como paga.',
        'aria_label' => 'Feedback sonoro',
    ],

    'sound_type' => [
        'title' => 'Tipo de Som',
        'description' => 'Escolha o som e ouça uma prévia.',
        'placeholder' => 'Selecione um som',
    ],

    'default_currency' => [
        'title' => 'Moeda Padrão',
        'description' => 'Moeda pré-selecionada ao criar lançamentos e contas.',
        'placeholder' => 'Selecione uma moeda',
        'none' => 'Sem preferência',
    ],

    'notifications' => [
        'title' => 'Notificações',
        'description' => 'Receba um e-mail quando uma transação vence hoje ou está prestes a vencer.',
        'aria_label' => 'Notificações',
    ],

    'reminder' => [
        'title' => 'Antecedência do Lembrete',
        'description' => 'Sempre notificamos na data de vencimento, além desta quantidade de dias antes.',
        'placeholder' => 'Selecione',
    ],

    'days_before' => [
        'n1' => '1 dia antes',
        'n3' => '3 dias antes',
        'n5' => '5 dias antes',
        'n7' => '7 dias antes',
        'n10' => '10 dias antes',
    ],
];
