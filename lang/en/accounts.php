<?php

return [
    'title' => 'Accounts',
    'subtitle' => 'Balances and statement of your accounts.',
    'empty' => 'No accounts. Create accounts in Settings.',
    'balance' => 'Current balance',
    'month_income' => 'Income this month',
    'month_expense' => 'Expenses this month',
    'total' => 'Total',

    'movement' => [
        'income' => 'In',
        'expense' => 'Out',
    ],

    'statement' => [
        'back' => 'Back to accounts',
        'opening' => 'Opening balance',
        'closing' => 'Closing balance',
        'income' => 'Income',
        'expense' => 'Expenses',
        'empty' => 'No movements in this period.',
        'status_paid' => 'Paid',
        'status_pending' => 'Pending',
        'columns' => [
            'date' => 'Date',
            'description' => 'Description',
            'type' => 'Type',
            'status' => 'Status',
            'amount' => 'Amount',
            'balance' => 'Balance',
        ],
    ],

    'invoice' => [
        'title' => 'Current invoice',
        'total' => 'Invoice total',
        'due_date' => 'Due date',
        'available' => 'Available limit',
        'limit' => 'Limit',
        'outstanding' => 'Outstanding balance',
        'period' => 'Period :start – :end',
        'empty' => 'No purchases on this invoice.',
        'columns' => [
            'date' => 'Date',
            'description' => 'Description',
            'category' => 'Category',
            'amount' => 'Amount',
        ],
        'pay' => [
            'action' => 'Pay invoice',
            'title' => 'Pay invoice',
            'description' => 'Record the invoice payment as a transfer from your account.',
            'account_label' => 'Source account',
            'account_placeholder' => 'Select the account',
            'amount_label' => 'Amount',
            'date_label' => 'Payment date',
            'no_accounts' => 'No account available in this currency.',
            'entry' => 'Invoice payment :card',
            'currency_mismatch' => 'The account must be in the same currency as the card.',
        ],
    ],
];
