<?php

return [
    'email_changed' => [
        'subject' => 'O e-mail da sua conta foi alterado',
        'greeting' => 'Olá, :name!',
        'intro' => 'O e-mail da sua conta foi alterado de :previous para :new.',
        'confirm' => 'Se foi você, não precisa fazer nada.',
        'warning' => 'Se não foi você, entre em contato conosco imediatamente: sua conta pode ter sido acessada por outra pessoa.',
    ],

    'transactions_due' => [
        'greeting' => 'Olá, :name!',
        'footer' => 'Acesse o plann.money para acompanhar suas transações.',

        'subject' => [
            'overdue' => 'Transações em atraso',
            'due_today' => 'Transações que vencem hoje',
            'upcoming' => 'Transações a vencer',
        ],

        'intro' => [
            'overdue' => 'Você tem transações vencidas e ainda não pagas:',
            'due_today' => 'Você tem transações que vencem hoje:',
            'upcoming' => 'Você tem transações que vão vencer em breve:',
        ],

        'item' => ':description — :amount (vence em :date, :account)',
        'item_overdue' => ':description — :amount (venceu em :date, :account)',
    ],
];
