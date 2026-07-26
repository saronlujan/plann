<?php

return [
    'title' => 'Transacciones',
    'subtitle' => 'Gestiona tus transacciones financieras, incluidos ingresos, gastos y transferencias.',

    'columns' => [
        'type' => 'Tipo',
        'amount' => 'Importe',
        'account' => 'Cuenta',
    ],

    'status' => [
        'paid' => 'Pagado',
        'paid_on' => 'Pagado el :date',
        'overdue' => 'Vencida',
        'due_soon' => 'Vence pronto',
        'open' => 'Pendiente',
    ],

    'movement' => [
        'expense' => 'Gasto',
        'income' => 'Ingreso',
        'transfer' => 'Transferencia',
    ],

    'schedule' => [
        'unique' => 'Único',
        'recurring' => 'Recurrente',
        'installment' => 'A plazos :number/:total',
    ],

    'summary' => [
        'income' => 'Ingreso',
        'expenses' => 'Gastos',
        'total' => 'Total',
        'expected_income' => 'Ingreso previsto',
        'expected_expense' => 'Gasto previsto',
        'expected_total' => 'Total previsto',
    ],

    'actions' => [
        'new' => 'Nueva transacción',
        'view' => 'Ver',
        'more_options' => 'Más opciones',
        'mark_paid' => 'Marcar como pagado',
        'mark_unpaid' => 'Marcar como no pagado',
        'remove_tag' => 'Quitar :name',
    ],

    'delete' => [
        'title' => 'Eliminar transacción',
        'description' => 'Eliminar «:label»? Esta acción no se puede deshacer.',
    ],

    'modal' => [
        'create_title' => 'Nueva transacción',
        'edit_title' => 'Editar transacción',
        'movement_type_group' => 'Tipo de transacción',
    ],

    'recurrence' => [
        'title' => 'Alcance de la recurrencia',
        'subtitle' => 'Elige qué ocurrencias de esta serie se verán afectadas.',
    ],

    'recurrence_scope' => [
        'all' => 'Toda la serie',
        'one' => 'Solo esta ocurrencia',
        'forward' => 'Esta y las futuras',
    ],

    'installment' => [
        'title' => 'Pago a plazos',
        'subtitle' => 'Indica la cantidad de cuotas y el período de cobro.',
    ],

    'fields' => [
        'type' => 'Tipo',
        'currency' => 'Moneda',
        'account' => 'Cuenta',
        'source_account' => 'Cuenta de origen',
        'destination_account' => 'Cuenta de destino',
        'effective_date' => 'Fecha efectiva',
        'amount' => 'Importe',
        'interest' => 'Interés de la cuenta',
        'description' => 'Descripción',
        'category' => 'Categoría',
        'tags' => 'Etiquetas',
        'repeat_until' => 'Repetir hasta (opcional)',
        'repeat_until_short' => 'Repetir hasta',
        'apply_change_to' => 'Aplicar cambio a',
        'installments_total' => 'Cantidad de cuotas',
        'frequency' => 'Período',
        'status' => 'Estado',
        'date' => 'Fecha',
        'installment' => 'Cuota',
        'adjustment' => 'Ajuste',
    ],

    'placeholders' => [
        'type' => 'Selecciona el tipo',
        'currency' => 'Selecciona la moneda',
        'account' => 'Selecciona la cuenta',
        'destination_account' => 'Selecciona la cuenta de destino',
        'amount' => '0,00',
        'description' => 'Describe la transacción',
        'category' => 'Selecciona la categoría',
        'no_categories' => 'No hay categorías registradas',
        'tags' => 'Selecciona etiquetas',
        'no_tags' => 'No hay etiquetas registradas',
        'no_tags_found' => 'No se encontraron etiquetas.',
        'search_tag' => 'Buscar etiqueta...',
        'scope' => 'Selecciona el alcance',
        'installments_total' => '12',
        'frequency' => 'Selecciona el período',
    ],

    'hints' => [
        'repeat_until' => 'Déjalo en blanco para una recurrencia sin fin.',
    ],
];
