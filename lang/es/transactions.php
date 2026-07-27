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

    'errors' => [
        'cannot_become_transfer' => 'No se puede convertir un movimiento en transferencia. Elimínalo y crea una transferencia nueva.',
    ],

    'defaults' => [
        'transfer_description' => 'Transferencia',
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
        'title' => 'Eliminar Transacción',
        'description' => 'Eliminar «:label»? Esta acción no se puede deshacer.',
    ],

    'modal' => [
        'create_title' => 'Nueva Transacción',
        'edit_title' => 'Editar Transacción',
        'movement_type_group' => 'Tipo de transacción',
    ],

    'recurrence' => [
        'title' => 'Esta es una transacción recurrente.',
    ],

    'recurrence_scope' => [
        'all' => 'Editar todas las transacciones',
        'one' => 'Editar solo esta transacción',
        'forward' => 'Editar esta y las próximas transacciones',
    ],

    'installment' => [
        'title' => 'Pago a Plazos',
        'subtitle' => 'Indica la cantidad de cuotas y el período de cobro.',
    ],

    'fields' => [
        'type' => 'Tipo',
        'currency' => 'Moneda',
        'account' => 'Cuenta',
        'source_account' => 'Cuenta de origen',
        'destination_account' => 'Cuenta de destino',
        'effective_date' => 'Fecha',
        'amount' => 'Importe',
        'interest' => 'Interés de la cuenta',
        'description' => 'Descripción',
        'description_optional' => 'Descripción (opcional)',
        'category' => 'Categoría',
        'tags' => 'Etiquetas',
        'repeat_until' => 'Repetir hasta (opcional)',
        'repeat_until_short' => 'Repetir hasta',
        'installments_total' => 'Cantidad de cuotas',
        'frequency' => 'Período',
        'status' => 'Estado',
        'date' => 'Fecha',
        'installment' => 'Cuota',
        'adjustment' => 'Ajuste',
        'attachment' => 'Adjuntar un archivo a esta transacción',
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
        'installments_total' => '12',
        'frequency' => 'Selecciona el período',
    ],

    'hints' => [
        'repeat_until' => 'Déjalo en blanco para una recurrencia sin fin.',
        'attachment' => 'Imagen (JPG, PNG, WEBP) o PDF, hasta 10 MB.',
    ],
];
