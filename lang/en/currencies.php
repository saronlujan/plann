<?php

return [
    'title' => 'Currencies',
    'subtitle' => 'Enable the currencies you use in accounts and transactions.',
    'subtitle_single' => 'Your plan uses one currency at a time. Pick which.',
    'activate' => 'Activate :code',

    'created' => 'Currency added.',
    'deleted' => 'Currency removed.',

    'add' => 'Add',
    'updated' => 'Currency updated.',

    'modal' => [
        'create_title' => 'New Currency',
        'edit_title' => 'Edit Currency',
        'description' => 'Currencies you register are visible only in this workspace.',
        'name' => 'Name',
        'name_placeholder' => 'e.g. Euro',
        'code' => 'Code',
        'code_placeholder' => 'EUR',
        'symbol' => 'Symbol',
        'symbol_placeholder' => '€',
    ],

    'missing_account_notice' => 'You have :codes not linked to an account, link it to use it in transactions.',
    'missing_account_notice_plural' => 'You have :codes not linked to an account, link them to use them in transactions.',
    'missing_account_cta' => 'Create account',
    'plan_notice' => 'Your plan keeps a single currency active.',
    'plan_cta' => 'See Pro',

    'custom_badge' => 'Yours',
    'delete_confirm' => 'Remove :code? Accounts in this currency will be deleted too. This cannot be undone.',

    'errors' => [
        'in_use' => 'This currency has entries and cannot be removed.',
        'plan_limit' => 'Your plan allows a single active currency. Upgrade to Pro to use more.',
    ],
];
