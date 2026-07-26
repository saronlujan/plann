<?php

return [
    'categories' => [
        'title' => 'Categories',
        'subtitle' => 'Income and expense categories.',
        'add' => 'Add category',
        'delete_confirm' => 'Delete “:name”? This action cannot be undone.',
        'columns' => [
            'name' => 'Name',
            'type' => 'Type',
        ],
        'modal' => [
            'create_title' => 'New category',
            'edit_title' => 'Edit category',
            'description' => 'Income or expense categories.',
            'name_label' => 'Name',
            'name_placeholder' => 'Category name',
            'type_label' => 'Type',
            'color_label' => 'Color',
        ],
    ],

    'tags' => [
        'title' => 'Tags',
        'subtitle' => 'Free-form labels to organize your transactions.',
        'add' => 'Add tag',
        'delete_confirm' => 'Delete “:name”? This action cannot be undone.',
        'columns' => [
            'name' => 'Name',
        ],
        'modal' => [
            'create_title' => 'New tag',
            'edit_title' => 'Edit tag',
            'description' => 'Free-form labels to organize transactions.',
            'name_label' => 'Name',
            'name_placeholder' => 'Tag name',
            'color_label' => 'Color',
        ],
    ],

    'accounts' => [
        'title' => 'Accounts',
        'subtitle' => 'Accounts in active currencies.',
        'add' => 'Add account',
        'delete_confirm' => 'Delete “:name”? This action cannot be undone.',
        'columns' => [
            'name' => 'Name',
            'kind' => 'Type',
            'currency' => 'Currency',
            'balance' => 'Balance',
        ],
        'modal' => [
            'create_title' => 'New account',
            'edit_title' => 'Edit account',
            'description' => 'Accounts in active currencies.',
            'name_label' => 'Name',
            'name_placeholder' => 'E.g., Checking Account',
            'kind_label' => 'Type',
            'currency_label' => 'Currency',
            'currency_placeholder' => 'Select the currency',
            'balance_label' => 'Initial balance',
            'balance_placeholder' => '0.00',
            'credit_limit_label' => 'Credit limit',
            'credit_limit_placeholder' => '0.00',
            'closing_day_label' => 'Closing day',
            'due_day_label' => 'Due day',
            'day_placeholder' => 'Day',
        ],
    ],

    'currencies' => [
        'title' => 'Currencies',
        'subtitle' => 'Enable the currencies you use in accounts and transactions.',
        'activate' => 'Activate :code',
    ],
];
