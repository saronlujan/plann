<?php

return [
    'categories' => [
        'title' => 'Categorías',
        'subtitle' => 'Categorías de ingresos y gastos.',
        'add' => 'Añadir categoría',
        'delete_confirm' => '¿Eliminar “:name”? Esta acción no se puede deshacer.',
        'columns' => [
            'name' => 'Nombre',
            'type' => 'Tipo',
        ],
        'modal' => [
            'create_title' => 'Nueva categoría',
            'edit_title' => 'Editar categoría',
            'description' => 'Categorías de ingresos o gastos.',
            'name_label' => 'Nombre',
            'name_placeholder' => 'Nombre de la categoría',
            'type_label' => 'Tipo',
            'color_label' => 'Color',
        ],
    ],

    'tags' => [
        'title' => 'Etiquetas',
        'subtitle' => 'Etiquetas libres para organizar tus transacciones.',
        'add' => 'Añadir etiqueta',
        'delete_confirm' => '¿Eliminar “:name”? Esta acción no se puede deshacer.',
        'columns' => [
            'name' => 'Nombre',
        ],
        'modal' => [
            'create_title' => 'Nueva etiqueta',
            'edit_title' => 'Editar etiqueta',
            'description' => 'Etiquetas libres para organizar transacciones.',
            'name_label' => 'Nombre',
            'name_placeholder' => 'Nombre de la etiqueta',
            'color_label' => 'Color',
        ],
    ],

    'accounts' => [
        'title' => 'Cuentas',
        'subtitle' => 'Cuentas en monedas activas.',
        'add' => 'Añadir cuenta',
        'delete_confirm' => '¿Eliminar “:name”? Esta acción no se puede deshacer.',
        'columns' => [
            'name' => 'Nombre',
            'kind' => 'Tipo',
            'currency' => 'Moneda',
            'balance' => 'Saldo',
        ],
        'modal' => [
            'create_title' => 'Nueva cuenta',
            'edit_title' => 'Editar cuenta',
            'description' => 'Cuentas en monedas activas.',
            'name_label' => 'Nombre',
            'name_placeholder' => 'Ej.: Cuenta Corriente',
            'kind_label' => 'Tipo',
            'currency_label' => 'Moneda',
            'currency_placeholder' => 'Selecciona la moneda',
            'balance_label' => 'Saldo inicial',
            'balance_placeholder' => '0,00',
            'credit_limit_label' => 'Límite de crédito',
            'credit_limit_placeholder' => '0,00',
            'closing_day_label' => 'Día de cierre',
            'due_day_label' => 'Día de vencimiento',
            'day_placeholder' => 'Día',
        ],
    ],

    'currencies' => [
        'title' => 'Monedas',
        'subtitle' => 'Habilita las monedas que usas en las cuentas y transacciones.',
        'activate' => 'Activar :code',
    ],
];
