<?php

return [
    'email_changed' => [
        'subject' => 'Your account email was changed',
        'greeting' => 'Hi, :name!',
        'intro' => 'The email on your account was changed from :previous to :new.',
        'confirm' => 'If this was you, no action is needed.',
        'warning' => 'If this was not you, contact us immediately — someone else may have access to your account.',
    ],

    'transactions_due' => [
        'greeting' => 'Hi, :name!',
        'footer' => 'Visit plann.money to review your transactions.',

        'subject' => [
            'overdue' => 'Overdue transactions',
            'due_today' => 'Transactions due today',
            'upcoming' => 'Upcoming transactions',
        ],

        'intro' => [
            'overdue' => 'You have transactions that are past due and still unpaid:',
            'due_today' => 'You have transactions due today:',
            'upcoming' => 'You have transactions coming due soon:',
        ],

        'item' => ':description — :amount (due on :date, :account)',
        'item_overdue' => ':description — :amount (was due on :date, :account)',
    ],
];
