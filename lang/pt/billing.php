<?php

return [
    'title' => 'Planos e Cobrança',
    'subtitle' => 'Escolha o plano ideal — cobrança anual, cancele quando quiser.',

    'status' => [
        'active' => 'Ativo',
        'trial' => 'Teste',
        'expired' => 'Expirado',
        'subscribed' => 'Assinatura ativa.',
        'trial_days' => 'Período de teste: :count dia(s) restante(s).',
        'trial_ended' => 'Seu período de teste terminou. Escolha um plano para continuar.',
    ],

    'manage_payment' => 'Gerenciar pagamento',

    'plan' => [
        'current_badge' => 'Atual',
        'per_month' => '/mês',
        'billed_annually' => 'cobrado anualmente :value',
        'no_features' => 'O essencial para o dia a dia.',
    ],

    'actions' => [
        'current' => 'Plano atual',
        'unavailable' => 'Indisponível',
        'subscribe' => 'Assinar',
        'switch_to' => 'Trocar para :name',
    ],

    'invoices' => [
        'title' => 'Faturas',
        'invoice' => 'Fatura',
        'date' => 'Data',
        'status' => 'Status',
        'total' => 'Total',
        'statuses' => [
            'draft' => 'Rascunho',
            'open' => 'Em aberto',
            'paid' => 'Paga',
            'uncollectible' => 'Não recebida',
            'void' => 'Cancelada',
        ],
        'empty' => 'Nenhuma fatura ainda.',
    ],

    'refresh' => [
        'found' => 'Assinatura confirmada. Acesso liberado!',
        'not_found' => 'Nenhuma assinatura ativa encontrada no Stripe.',
        'failed' => 'Não conseguimos consultar o Stripe agora. Tente em instantes.',
        'action' => 'Já paguei, atualizar status',
    ],
];
