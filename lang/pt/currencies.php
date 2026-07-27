<?php

return [
    'title' => 'Moedas',
    'subtitle' => 'Habilite as moedas que você usa nas contas e transações.',
    'subtitle_single' => 'Seu plano usa uma moeda por vez. Escolha qual.',
    'activate' => 'Ativar :code',

    'created' => 'Moeda adicionada.',
    'deleted' => 'Moeda removida.',

    'add' => 'Adicionar',
    'updated' => 'Moeda atualizada.',

    'modal' => [
        'create_title' => 'Nova Moeda',
        'edit_title' => 'Editar Moeda',
        'description' => 'Moedas que você cadastra ficam visíveis só neste espaço.',
        'name' => 'Nome',
        'name_placeholder' => 'Ex.: Euro',
        'code' => 'Código',
        'code_placeholder' => 'EUR',
        'symbol' => 'Símbolo',
        'symbol_placeholder' => '€',
    ],

    'missing_account_notice' => 'Você tem :codes que não está vinculada a uma conta, vincule para poder utilizá-la nas transações.',
    'missing_account_notice_plural' => 'Você tem :codes que não estão vinculadas a uma conta, vincule para poder utilizá-las nas transações.',
    'missing_account_cta' => 'Criar conta',
    'plan_notice' => 'Seu plano mantém apenas uma moeda ativa.',
    'plan_cta' => 'Conhecer o Pro',

    'custom_badge' => 'Sua',
    'delete_confirm' => 'Remover :code? As contas nesta moeda também serão excluídas. Esta ação não pode ser desfeita.',

    'errors' => [
        'in_use' => 'Esta moeda tem lançamentos e não pode ser removida.',
        'plan_limit' => 'Seu plano permite apenas uma moeda ativa. Faça upgrade para o Pro para usar várias.',
    ],
];
