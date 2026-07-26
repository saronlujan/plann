<?php

return [
    'categories' => [
        'title' => 'Categorias',
        'subtitle' => 'Categorias de receita e despesa.',
        'add' => 'Adicionar categoria',
        'delete_confirm' => 'Excluir “:name”? Esta ação não pode ser desfeita.',
        'columns' => [
            'name' => 'Nome',
            'type' => 'Tipo',
        ],
        'modal' => [
            'create_title' => 'Nova categoria',
            'edit_title' => 'Editar categoria',
            'description' => 'Categorias de receita ou despesa.',
            'name_label' => 'Nome',
            'name_placeholder' => 'Nome da categoria',
            'type_label' => 'Tipo',
            'color_label' => 'Cor',
        ],
    ],

    'tags' => [
        'title' => 'Tags',
        'subtitle' => 'Rótulos livres para organizar suas transações.',
        'add' => 'Adicionar tag',
        'delete_confirm' => 'Excluir “:name”? Esta ação não pode ser desfeita.',
        'columns' => [
            'name' => 'Nome',
        ],
        'modal' => [
            'create_title' => 'Nova tag',
            'edit_title' => 'Editar tag',
            'description' => 'Rótulos livres para organizar transações.',
            'name_label' => 'Nome',
            'name_placeholder' => 'Nome da tag',
            'color_label' => 'Cor',
        ],
    ],

    'accounts' => [
        'title' => 'Contas',
        'subtitle' => 'Contas em moedas ativas.',
        'add' => 'Adicionar conta',
        'delete_confirm' => 'Excluir “:name”? Esta ação não pode ser desfeita.',
        'columns' => [
            'name' => 'Nome',
            'kind' => 'Tipo',
            'currency' => 'Moeda',
            'balance' => 'Saldo',
        ],
        'modal' => [
            'create_title' => 'Nova conta',
            'edit_title' => 'Editar conta',
            'description' => 'Contas em moedas ativas.',
            'name_label' => 'Nome',
            'name_placeholder' => 'Ex.: Conta Corrente',
            'kind_label' => 'Tipo',
            'currency_label' => 'Moeda',
            'currency_placeholder' => 'Selecione a moeda',
            'balance_label' => 'Saldo inicial',
            'balance_placeholder' => '0,00',
            'credit_limit_label' => 'Limite de crédito',
            'credit_limit_placeholder' => '0,00',
            'closing_day_label' => 'Dia de fechamento',
            'due_day_label' => 'Dia de vencimento',
            'day_placeholder' => 'Dia',
        ],
    ],

    'currencies' => [
        'title' => 'Moedas',
        'subtitle' => 'Habilite as moedas que você usa nas contas e transações.',
        'activate' => 'Ativar :code',
    ],
];
