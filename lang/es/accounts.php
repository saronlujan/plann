<?php

return [
    'title' => 'Cuentas',
    'subtitle' => 'Saldos y extracto de tus cuentas.',
    'empty' => 'Sin cuentas. Crea cuentas en Ajustes.',
    'balance' => 'Saldo actual',
    'month_income' => 'Ingresos del mes',
    'month_expense' => 'Gastos del mes',
    'total' => 'Total',

    'movement' => [
        'income' => 'Entrada',
        'expense' => 'Salida',
    ],

    'statement' => [
        'back' => 'Volver a cuentas',
        'opening' => 'Saldo inicial',
        'closing' => 'Saldo final',
        'income' => 'Ingresos',
        'expense' => 'Gastos',
        'empty' => 'Sin movimientos en este período.',
        'status_paid' => 'Pagado',
        'status_pending' => 'Pendiente',
        'columns' => [
            'date' => 'Fecha',
            'description' => 'Descripción',
            'type' => 'Tipo',
            'status' => 'Estado',
            'amount' => 'Importe',
            'balance' => 'Saldo',
        ],
    ],

    'invoice' => [
        'title' => 'Factura actual',
        'total' => 'Total de la factura',
        'due_date' => 'Vencimiento',
        'available' => 'Límite disponible',
        'limit' => 'Límite',
        'outstanding' => 'Saldo adeudado',
        'period' => 'Período :start – :end',
        'empty' => 'Sin compras en esta factura.',
        'columns' => [
            'date' => 'Fecha',
            'description' => 'Descripción',
            'category' => 'Categoría',
            'amount' => 'Importe',
        ],
        'pay' => [
            'action' => 'Pagar factura',
            'title' => 'Pagar factura',
            'description' => 'Registra el pago de la factura como una transferencia desde tu cuenta.',
            'account_label' => 'Cuenta de origen',
            'account_placeholder' => 'Selecciona la cuenta',
            'amount_label' => 'Importe',
            'date_label' => 'Fecha del pago',
            'no_accounts' => 'Ninguna cuenta disponible en esta moneda.',
            'entry' => 'Pago factura :card',
            'currency_mismatch' => 'La cuenta debe estar en la misma moneda que la tarjeta.',
        ],
    ],
];
