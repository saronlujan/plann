<?php

return [
    'title' => 'Contas',
    'subtitle' => 'Saldos e extrato das suas contas.',
    'empty' => 'Nenhuma conta. Crie contas em Configurações.',
    'balance' => 'Saldo atual',
    'month_income' => 'Entradas do mês',
    'month_expense' => 'Saídas do mês',
    'total' => 'Total',

    'movement' => [
        'income' => 'Entrada',
        'expense' => 'Saída',
    ],

    'statement' => [
        'back' => 'Voltar para contas',
        'opening' => 'Saldo inicial',
        'closing' => 'Saldo final',
        'income' => 'Entradas',
        'expense' => 'Saídas',
        'empty' => 'Sem movimentos neste período.',
        'status_paid' => 'Pago',
        'status_pending' => 'Em aberto',
        'columns' => [
            'date' => 'Data',
            'description' => 'Descrição',
            'type' => 'Tipo',
            'status' => 'Status',
            'amount' => 'Valor',
            'balance' => 'Saldo',
        ],
    ],

    'invoice' => [
        'title' => 'Fatura atual',
        'total' => 'Total da fatura',
        'due_date' => 'Vencimento',
        'available' => 'Limite disponível',
        'limit' => 'Limite',
        'outstanding' => 'Saldo devedor',
        'period' => 'Período :start – :end',
        'empty' => 'Sem compras nesta fatura.',
        'columns' => [
            'date' => 'Data',
            'description' => 'Descrição',
            'category' => 'Categoria',
            'amount' => 'Valor',
        ],
        'pay' => [
            'action' => 'Pagar fatura',
            'title' => 'Pagar fatura',
            'description' => 'Registre o pagamento da fatura como uma transferência da sua conta.',
            'account_label' => 'Conta de origem',
            'account_placeholder' => 'Selecione a conta',
            'amount_label' => 'Valor',
            'date_label' => 'Data do pagamento',
            'no_accounts' => 'Nenhuma conta disponível nesta moeda.',
            'entry' => 'Pagamento fatura :card',
            'currency_mismatch' => 'A conta deve estar na mesma moeda do cartão.',
        ],
    ],
];
