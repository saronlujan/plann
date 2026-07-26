<?php

return [
    'title' => 'Transações',
    'subtitle' => 'Gerencie suas transações financeiras, incluindo receitas, despesas e transferências.',

    'columns' => [
        'type' => 'Tipo',
        'amount' => 'Valor',
        'account' => 'Conta',
    ],

    'status' => [
        'paid' => 'Pago',
        'paid_on' => 'Pago em :date',
        'overdue' => 'Vencida',
        'due_soon' => 'Vence em breve',
        'open' => 'Em aberto',
    ],

    'movement' => [
        'expense' => 'Despesa',
        'income' => 'Receita',
        'transfer' => 'Transferência',
    ],

    'schedule' => [
        'unique' => 'Único',
        'recurring' => 'Recorrente',
        'installment' => 'Parcelado :number/:total',
    ],

    'summary' => [
        'income' => 'Receita',
        'expenses' => 'Despesas',
        'total' => 'Total',
        'expected_income' => 'Receita prevista',
        'expected_expense' => 'Despesa prevista',
        'expected_total' => 'Total previsto',
    ],

    'actions' => [
        'new' => 'Nova transação',
        'view' => 'Exibir',
        'more_options' => 'Mais opções',
        'mark_paid' => 'Marcar como pago',
        'mark_unpaid' => 'Marcar como não pago',
        'remove_tag' => 'Remover :name',
    ],

    'delete' => [
        'title' => 'Excluir transação',
        'description' => 'Excluir “:label”? Esta ação não pode ser desfeita.',
    ],

    'modal' => [
        'create_title' => 'Nova transação',
        'edit_title' => 'Editar transação',
        'movement_type_group' => 'Tipo de transação',
    ],

    'recurrence' => [
        'title' => 'Escopo da recorrência',
        'subtitle' => 'Escolha quais ocorrências desta série serão afetadas.',
    ],

    'recurrence_scope' => [
        'all' => 'Toda a série',
        'one' => 'Somente esta ocorrência',
        'forward' => 'Esta e as futuras',
    ],

    'installment' => [
        'title' => 'Parcelamento',
        'subtitle' => 'Informe a quantidade de parcelas e o período de cobrança.',
    ],

    'fields' => [
        'type' => 'Tipo',
        'currency' => 'Moeda',
        'account' => 'Conta',
        'source_account' => 'Conta de origem',
        'destination_account' => 'Conta de destino',
        'effective_date' => 'Data efetiva',
        'amount' => 'Valor',
        'interest' => 'Juros da conta',
        'description' => 'Descrição',
        'category' => 'Categoria',
        'tags' => 'Tags',
        'repeat_until' => 'Repetir até (opcional)',
        'repeat_until_short' => 'Repetir até',
        'apply_change_to' => 'Aplicar alteração a',
        'installments_total' => 'Quantidade de parcelas',
        'frequency' => 'Período',
        'status' => 'Status',
        'date' => 'Data',
        'installment' => 'Parcela',
        'adjustment' => 'Ajuste',
    ],

    'placeholders' => [
        'type' => 'Selecione o tipo',
        'currency' => 'Selecione a moeda',
        'account' => 'Selecione a conta',
        'destination_account' => 'Selecione a conta de destino',
        'amount' => '0,00',
        'description' => 'Descreva a transação',
        'category' => 'Selecione a categoria',
        'no_categories' => 'Nenhuma categoria cadastrada',
        'tags' => 'Selecione tags',
        'no_tags' => 'Nenhuma tag cadastrada',
        'no_tags_found' => 'Nenhuma tag encontrada.',
        'search_tag' => 'Buscar tag...',
        'scope' => 'Selecione o escopo',
        'installments_total' => '12',
        'frequency' => 'Selecione o período',
    ],

    'hints' => [
        'repeat_until' => 'Deixe em branco para uma recorrência sem fim.',
    ],
];
