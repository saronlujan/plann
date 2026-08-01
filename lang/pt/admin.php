<?php

return [
    'title' => 'Admin',

    'nav' => [
        'dashboard' => 'Painel',
        'tenants' => 'Clientes',
        'back_to_app' => 'Voltar ao app',
    ],

    'dashboard' => [
        'title' => 'Painel',
        'subtitle' => 'Como a plataforma está indo.',
        'recent' => 'Clientes recentes',
    ],

    'stats' => [
        'tenants' => 'Clientes',
        'subscribers' => 'Assinantes ativos',
        'trialing' => 'Em teste',
        'revenue' => 'Receita mensal',
        'revenue_hint' => 'Recorrente das assinaturas ativas, não o total já recebido.',
    ],

    'tenants' => [
        'title' => 'Clientes',
        'subtitle' => ':count no total.',
        'search' => 'Buscar por nome ou e-mail',
        'empty' => 'Nenhum cliente encontrado.',
        'back' => 'Voltar para clientes',
        'page' => 'Página :current de :last',
        'previous' => 'Anterior',
        'next' => 'Próxima',
    ],

    'columns' => [
        'tenant' => 'Cliente',
        'name' => 'Nome',
        'email' => 'E-mail',
        'plan' => 'Plano',
        'status' => 'Situação',
        'created_at' => 'Cadastro',
    ],

    'status' => [
        'subscribed' => 'Assinante',
        'trialing' => 'Em teste',
        'lapsed' => 'Expirado',
    ],

    'show' => [
        'account' => 'Conta',
        'billing' => 'Cobrança',
        'verified' => 'E-mail verificado',
        'trial_ends_at' => 'Teste até',
        'stripe_id' => 'ID no Stripe',
    ],
];
