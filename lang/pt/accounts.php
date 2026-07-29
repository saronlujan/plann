<?php

return [
    'errors' => [
        'currency_locked' => 'Esta conta já tem lançamentos. Resolva os lançamentos antes de trocar a moeda.',
        'plan_limit' => 'Seu plano permite apenas uma moeda. Assine o Pro para usar mais.',
    ],

    'title' => 'Contas',
    'subtitle' => 'Saldos e extrato das suas contas.',
    'empty' => 'Você ainda não tem contas. Crie a primeira no botão acima.',
    'balance' => 'Saldo atual',
    'month_income' => 'Entradas do mês',
    'month_expense' => 'Saídas do mês',
    'total' => 'Total',

    'add' => 'Adicionar conta',
    'delete_confirm' => 'Excluir “:name”? Esta ação não pode ser desfeita.',
    'delete_in_use' => 'Esta conta tem lançamentos e não pode ser excluída.',

    'modal' => [
        'create_title' => 'Nova Conta',
        'edit_title' => 'Editar Conta',
        'description' => 'Contas em moedas ativas.',
        'name_label' => 'Nome',
        'name_placeholder' => 'Ex.: Conta Corrente',
        'kind_label' => 'Tipo',
        'currency_label' => 'Moeda',
        'currency_locked' => 'A moeda não pode ser alterada: a conta já tem lançamentos.',
        'currency_placeholder' => 'Selecione a moeda',
        'credit_limit_label' => 'Limite de crédito',
        'credit_limit_placeholder' => '0,00',
        'closing_day_label' => 'Dia de fechamento',
        'due_day_label' => 'Dia de vencimento',
        'day_placeholder' => 'Dia',
    ],

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
        'title' => 'Fatura Atual',
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
            'title' => 'Pagar Fatura',
            'description' => 'Registre o pagamento da fatura como uma transferência da sua conta.',
            'account_label' => 'Conta de origem',
            'account_placeholder' => 'Selecione a conta',
            'amount_label' => 'Valor',
            'date_label' => 'Data do pagamento',
            'no_accounts' => 'Nenhuma conta disponível nesta moeda.',
            'currency_mismatch' => 'A conta deve estar na mesma moeda do cartão.',
        ],
    ],
];
