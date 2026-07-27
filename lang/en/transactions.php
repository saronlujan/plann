<?php

return [
    'title' => 'Transactions',
    'subtitle' => 'Manage your financial transactions, including income, expenses, and transfers.',

    'columns' => [
        'type' => 'Type',
        'amount' => 'Amount',
        'account' => 'Account',
    ],

    'status' => [
        'paid' => 'Paid',
        'paid_on' => 'Paid on :date',
        'overdue' => 'Overdue',
        'due_soon' => 'Due soon',
        'open' => 'Open',
    ],

    'errors' => [
        'cannot_become_transfer' => 'An entry cannot become a transfer. Delete it and create a new transfer instead.',
    ],

    'defaults' => [
        'transfer_description' => 'Transfer',
    ],

    'movement' => [
        'expense' => 'Expense',
        'income' => 'Income',
        'transfer' => 'Transfer',
    ],

    'schedule' => [
        'unique' => 'One-off',
        'recurring' => 'Recurring',
        'installment' => 'Installment :number/:total',
    ],

    'summary' => [
        'income' => 'Income',
        'expenses' => 'Expenses',
        'total' => 'Total',
        'expected_income' => 'Expected income',
        'expected_expense' => 'Expected expense',
        'expected_total' => 'Expected total',
    ],

    'actions' => [
        'new' => 'New transaction',
        'view' => 'View',
        'more_options' => 'More options',
        'mark_paid' => 'Mark as paid',
        'mark_unpaid' => 'Mark as unpaid',
        'remove_tag' => 'Remove :name',
    ],

    'delete' => [
        'title' => 'Delete Transaction',
        'description' => 'Delete “:label”? This action cannot be undone.',
    ],

    'modal' => [
        'create_title' => 'New Transaction',
        'edit_title' => 'Edit Transaction',
        'movement_type_group' => 'Transaction type',
    ],

    'recurrence' => [
        'title' => 'This is a recurring transaction.',
    ],

    'recurrence_scope' => [
        'all' => 'Edit all transactions',
        'one' => 'Edit only this transaction',
        'forward' => 'Edit this and the following transactions',
    ],

    'installment' => [
        'title' => 'Installments',
        'subtitle' => 'Enter the number of installments and the billing period.',
    ],

    'fields' => [
        'type' => 'Type',
        'currency' => 'Currency',
        'account' => 'Account',
        'source_account' => 'Source account',
        'destination_account' => 'Destination account',
        'effective_date' => 'Date',
        'amount' => 'Amount',
        'interest' => 'Account interest',
        'description' => 'Description',
        'description_optional' => 'Description (optional)',
        'category' => 'Category',
        'tags' => 'Tags',
        'repeat_until' => 'Repeat until (optional)',
        'repeat_until_short' => 'Repeat until',
        'installments_total' => 'Number of installments',
        'frequency' => 'Period',
        'status' => 'Status',
        'date' => 'Date',
        'installment' => 'Installment',
        'adjustment' => 'Adjustment',
        'attachment' => 'Attach a file to this transaction',
    ],

    'placeholders' => [
        'type' => 'Select the type',
        'currency' => 'Select the currency',
        'account' => 'Select the account',
        'destination_account' => 'Select the destination account',
        'amount' => '0.00',
        'description' => 'Describe the transaction',
        'category' => 'Select the category',
        'no_categories' => 'No categories registered',
        'tags' => 'Select tags',
        'no_tags' => 'No tags registered',
        'no_tags_found' => 'No tags found.',
        'search_tag' => 'Search tag...',
        'installments_total' => '12',
        'frequency' => 'Select the period',
    ],

    'hints' => [
        'repeat_until' => 'Leave blank for an endless recurrence.',
        'attachment' => 'Image (JPG, PNG, WEBP) or PDF, up to 10 MB.',
    ],
];
