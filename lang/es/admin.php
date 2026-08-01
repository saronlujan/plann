<?php

return [
    'title' => 'Admin',

    'nav' => [
        'dashboard' => 'Panel',
        'tenants' => 'Clientes',
        'back_to_app' => 'Volver a la app',
    ],

    'dashboard' => [
        'title' => 'Panel',
        'subtitle' => 'Cómo va la plataforma.',
        'recent' => 'Clientes recientes',
    ],

    'stats' => [
        'tenants' => 'Clientes',
        'subscribers' => 'Suscriptores activos',
        'trialing' => 'En prueba',
        'revenue' => 'Ingreso mensual',
        'revenue_hint' => 'Recurrente de las suscripciones activas, no el total ya cobrado.',
    ],

    'tenants' => [
        'title' => 'Clientes',
        'subtitle' => ':count en total.',
        'search' => 'Buscar por nombre o correo',
        'empty' => 'No se encontraron clientes.',
        'back' => 'Volver a clientes',
        'page' => 'Página :current de :last',
        'previous' => 'Anterior',
        'next' => 'Siguiente',
    ],

    'columns' => [
        'tenant' => 'Cliente',
        'name' => 'Nombre',
        'email' => 'Correo',
        'plan' => 'Plan',
        'status' => 'Estado',
        'created_at' => 'Alta',
    ],

    'status' => [
        'subscribed' => 'Suscriptor',
        'trialing' => 'En prueba',
        'lapsed' => 'Vencido',
    ],

    'show' => [
        'account' => 'Cuenta',
        'billing' => 'Facturación',
        'verified' => 'Correo verificado',
        'trial_ends_at' => 'Prueba hasta',
        'stripe_id' => 'ID en Stripe',
    ],
];
