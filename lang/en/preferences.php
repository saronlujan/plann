<?php

return [
    'title' => 'Preferences',
    'subtitle' => 'Language, theme, and accent color of the interface.',

    'language' => [
        'title' => 'Language',
        'description' => 'Select the language of the interface.',
        'placeholder' => 'Selecione o idioma',
    ],

    'theme' => [
        'title' => 'Theme',
        'description' => 'Select the theme of the interface.',
    ],

    'color' => [
        'title' => 'Color',
        'description' => 'Select the primary color applied to interface details.',
    ],

    'sound' => [
        'title' => 'Sound',
        'description' => 'Play a short sound when a transaction is marked as paid.',
        'aria_label' => 'Sound feedback',
    ],

    'sound_type' => [
        'title' => 'Sound type',
        'description' => 'Choose the sound and preview it.',
        'placeholder' => 'Select a sound',
    ],

    'default_currency' => [
        'title' => 'Default Currency',
        'description' => 'Pre-selected currency when creating transactions and accounts.',
        'placeholder' => 'Select a currency',
        'none' => 'No preference',
    ],

    'notifications' => [
        'title' => 'Notifications',
        'description' => 'Get an email when a transaction is due today or coming due.',
        'aria_label' => 'Notifications',
    ],

    'reminder' => [
        'title' => 'Reminder timing',
        'description' => 'We always notify on the due date, plus this many days before.',
        'placeholder' => 'Select',
    ],

    'days_before' => [
        'n1' => '1 day before',
        'n3' => '3 days before',
        'n5' => '5 days before',
        'n7' => '7 days before',
        'n10' => '10 days before',
    ],
];
