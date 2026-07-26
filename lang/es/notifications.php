<?php

return [
    'email_changed' => [
        'subject' => 'El correo de tu cuenta fue modificado',
        'greeting' => '¡Hola, :name!',
        'intro' => 'El correo de tu cuenta fue cambiado de :previous a :new.',
        'confirm' => 'Si fuiste tú, no necesitas hacer nada.',
        'warning' => 'Si no fuiste tú, contáctanos de inmediato: es posible que alguien más haya accedido a tu cuenta.',
    ],

    'transactions_due' => [
        'greeting' => '¡Hola, :name!',
        'footer' => 'Entra en plann.money para revisar tus transacciones.',

        'subject' => [
            'overdue' => 'Transacciones vencidas',
            'due_today' => 'Transacciones que vencen hoy',
            'upcoming' => 'Transacciones por vencer',
        ],

        'intro' => [
            'overdue' => 'Tienes transacciones vencidas y aún sin pagar:',
            'due_today' => 'Tienes transacciones que vencen hoy:',
            'upcoming' => 'Tienes transacciones que vencerán pronto:',
        ],

        'item' => ':description — :amount (vence el :date, :account)',
        'item_overdue' => ':description — :amount (venció el :date, :account)',
    ],
];
