<?php

return [
    'title' => 'Planes y facturación',
    'subtitle' => 'Elige el plan ideal — facturación anual, cancela cuando quieras.',

    'status' => [
        'active' => 'Activo',
        'trial' => 'Prueba',
        'expired' => 'Expirado',
        'subscribed' => 'Suscripción activa.',
        'trial_days' => 'Período de prueba: :count día(s) restante(s).',
        'trial_ended' => 'Tu período de prueba ha terminado. Elige un plan para continuar.',
    ],

    'manage_payment' => 'Gestionar pago',

    'plan' => [
        'current_badge' => 'Actual',
        'per_month' => '/mes',
        'billed_annually' => 'facturado anualmente :value',
        'no_features' => 'Lo esencial para el día a día.',
    ],

    'actions' => [
        'current' => 'Plan actual',
        'unavailable' => 'No disponible',
        'subscribe' => 'Suscribirse',
        'switch_to' => 'Cambiar a :name',
    ],

    'invoices' => [
        'title' => 'Facturas',
        'invoice' => 'Factura',
        'date' => 'Fecha',
        'status' => 'Estado',
        'total' => 'Total',
        'statuses' => [
            'draft' => 'Borrador',
            'open' => 'Pendiente',
            'paid' => 'Pagada',
            'uncollectible' => 'Incobrable',
            'void' => 'Anulada',
        ],
        'empty' => 'Aún no hay facturas.',
    ],

    'refresh' => [
        'found' => 'Suscripción confirmada. ¡Acceso liberado!',
        'not_found' => 'No encontramos una suscripción activa en Stripe.',
        'failed' => 'No pudimos consultar Stripe ahora. Inténtalo en un momento.',
        'action' => 'Ya pagué, actualizar estado',
    ],
];
