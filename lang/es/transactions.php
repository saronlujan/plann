<?php

return [
    'title' => 'Transacciones',
    'subtitle' => 'Gestiona tus transacciones financieras, incluidos ingresos, gastos y transferencias.',

    'columns' => [
        'transaction' => 'Transacción',
        'date' => 'Fecha',
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
        'realized_group' => 'Realizado',
        'expected_group' => 'Previsto',
        'see_details' => 'Ver detalles',
        'drawer_title' => 'Período Seleccionado',
        'drawer_hint' => 'El saldo realizado significa que ya fue pagado. El saldo previsto es lo que se espera recibir hasta el fin del período.',
    ],

    'actions' => [
        'new' => 'Nueva transacción',
        'view' => 'Ver',
        'more_options' => 'Más opciones',
        'mark_paid' => 'Marcar como pagado',
        'mark_unpaid' => 'Marcar como no pagado',
        'remove_tag' => 'Quitar :name',
        'open_attachment' => 'Abrir adjunto',
    ],

    'delete' => [
        'title' => 'Eliminar Transacción',
        'description' => 'Eliminar «:label»? Esta acción no se puede deshacer.',
        'scope' => [
            'title' => 'Este es un movimiento recurrente.',
            'one' => 'Eliminar solo este movimiento',
            'forward' => 'Eliminar este y los próximos movimientos',
            'all' => 'Eliminar todos los movimientos',
        ],
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

    'attachment' => [
        'choose' => 'Elegir archivo o arrastrarlo aquí',
        'replace' => 'Cambiar archivo',
        'formats' => 'Imagen (JPG, PNG, WEBP) o PDF, hasta 10 MB',
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
        'note' => 'Nota',
        'observations' => 'Observaciones y comentarios',
        'category' => 'Categoría',
        'contact' => 'Contacto',
        'services' => 'Servicios',
        'extras' => 'Agregar adjunto o notas',
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
        'attachment_label' => 'Adjunto',
        'paid' => 'Pagado',
    ],

    'placeholders' => [
        'type' => 'Selecciona el tipo',
        'currency' => 'Selecciona la moneda',
        'account' => 'Selecciona la cuenta',
        'destination_account' => 'Selecciona la cuenta de destino',
        'amount' => '0,00',
        'description' => 'Describe la transacción',
        'note' => 'Ej.: número de pedido, contrato',
        'observations' => 'Detalles que no caben en la descripción',
        'category' => 'Selecciona la categoría',
        'no_categories' => 'No hay categorías registradas',
        'contact' => 'Selecciona el contacto',
        'no_contacts' => 'Sin contactos registrados',
        'tags' => 'Selecciona etiquetas',
        'no_tags' => 'No hay etiquetas registradas',
        'no_tags_found' => 'No se encontraron etiquetas.',
        'search_tag' => 'Buscar etiqueta...',
        'installments_total' => '12',
        'frequency' => 'Selecciona el período',
    ],

    'hints' => [
        'repeat_until' => 'Déjalo en blanco para una recurrencia sin fin.',
    ],

    'services' => [
        'add' => 'Agregar servicio',
        'remove' => 'Quitar servicio',
        'unattributed' => 'Sin atribuir',
        'total_hint' => 'Sumado a partir de los servicios.',
    ],
    'filters' => [
        'title' => 'Filtros',
        'description' => 'Acota la lista. Elegir dos fechas reemplaza el período.',
        'more' => 'Filtros',
        'search' => 'Buscar',
        'search_placeholder' => 'Descripción, nota o cuenta',
        'previous' => 'Período anterior',
        'next' => 'Período siguiente',
        'movement' => 'Tipo',
        'any' => 'Todos',
        'status' => 'Estado',
        'status_paid' => 'Pagados',
        'status_pending' => 'Pendientes',
        'order' => 'Ordenar por',
        'order_date_desc' => 'Fecha (más reciente)',
        'order_date_asc' => 'Fecha (más antigua)',
        'order_amount_desc' => 'Monto (mayor)',
        'order_amount_asc' => 'Monto (menor)',
        'from' => 'Desde',
        'to' => 'Hasta',
        'range_hint' => 'Completa ambas fechas para usar un intervalo propio.',
        'range' => 'Período personalizado',
        'clear_range' => 'Limpiar intervalo',
        'apply' => 'Aplicar',
        'clear' => 'Limpiar',
    ],
];
