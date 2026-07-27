<?php

return [
    'title' => 'Monedas',
    'subtitle' => 'Habilita las monedas que usas en las cuentas y transacciones.',
    'subtitle_single' => 'Tu plan usa una moneda a la vez. Elige cuál.',
    'activate' => 'Activar :code',

    'created' => 'Moneda agregada.',
    'deleted' => 'Moneda eliminada.',

    'add' => 'Agregar',
    'updated' => 'Moneda actualizada.',

    'modal' => [
        'create_title' => 'Nueva Moneda',
        'edit_title' => 'Editar Moneda',
        'description' => 'Las monedas que registras solo son visibles en este espacio.',
        'name' => 'Nombre',
        'name_placeholder' => 'Ej.: Euro',
        'code' => 'Código',
        'code_placeholder' => 'EUR',
        'symbol' => 'Símbolo',
        'symbol_placeholder' => '€',
    ],

    'missing_account_notice' => 'Tienes :codes que no está vinculada a una cuenta, vincúlala para poder usarla en las transacciones.',
    'missing_account_notice_plural' => 'Tienes :codes que no están vinculadas a una cuenta, vincúlalas para poder usarlas en las transacciones.',
    'missing_account_cta' => 'Crear cuenta',
    'plan_notice' => 'Tu plan mantiene solo una moneda activa.',
    'plan_cta' => 'Conocer Pro',

    'custom_badge' => 'Tuya',
    'delete_confirm' => '¿Eliminar :code? Las cuentas en esta moneda también se eliminarán. Esta acción no se puede deshacer.',

    'errors' => [
        'in_use' => 'Esta moneda tiene movimientos y no puede eliminarse.',
        'plan_limit' => 'Tu plan permite solo una moneda activa. Cambia a Pro para usar varias.',
    ],
];
