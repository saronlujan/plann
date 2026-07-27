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

    'errors' => [
        'cannot_become_transfer' => 'Não é possível transformar um lançamento em transferência. Exclua e crie uma transferência nova.',
    ],

    'defaults' => [
        'transfer_description' => 'Transferência',
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
        'title' => 'Excluir Transação',
        'description' => 'Excluir “:label”? Esta ação não pode ser desfeita.',
    ],

    'modal' => [
        'create_title' => 'Nova Transação',
        'edit_title' => 'Editar Transação',
        'movement_type_group' => 'Tipo de transação',
    ],

    'recurrence' => [
        'title' => 'Esta é uma transação recorrente.',
    ],

    'recurrence_scope' => [
        'all' => 'Editar todas as transações',
        'one' => 'Editar apenas esta transação',
        'forward' => 'Editar esta e as próximas transações',
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
        'effective_date' => 'Data',
        'amount' => 'Valor',
        'interest' => 'Juros da conta',
        'description' => 'Descrição',
        'description_optional' => 'Descrição (opcional)',
        'category' => 'Categoria',
        'tags' => 'Tags',
        'repeat_until' => 'Repetir até (opcional)',
        'repeat_until_short' => 'Repetir até',
        'installments_total' => 'Quantidade de parcelas',
        'frequency' => 'Período',
        'status' => 'Status',
        'date' => 'Data',
        'installment' => 'Parcela',
        'adjustment' => 'Ajuste',
        'attachment' => 'Adicionar anexo desta transação',
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
        'installments_total' => '12',
        'frequency' => 'Selecione o período',
    ],

    'hints' => [
        'repeat_until' => 'Deixe em branco para uma recorrência sem fim.',
        'attachment' => 'Imagem (JPG, PNG, WEBP) ou PDF, até 10 MB.',
    ],
];
