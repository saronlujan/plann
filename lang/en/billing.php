<?php

return [
    'title' => 'Plans and Billing',
    'subtitle' => 'Choose the ideal plan — billed annually, cancel anytime.',

    'status' => [
        'active' => 'Active',
        'trial' => 'Trial',
        'expired' => 'Expired',
        'subscribed' => 'Subscription active.',
        'trial_days' => 'Trial period: :count day(s) remaining.',
        'trial_ended' => 'Your trial period has ended. Choose a plan to continue.',
    ],

    'manage_payment' => 'Manage payment',

    'plan' => [
        'current_badge' => 'Current',
        'per_month' => '/month',
        'billed_annually' => 'billed annually :value',
        'no_features' => 'The essentials for everyday use.',
    ],

    'actions' => [
        'current' => 'Current plan',
        'unavailable' => 'Unavailable',
        'subscribe' => 'Subscribe',
        'switch_to' => 'Switch to :name',
    ],

    'invoices' => [
        'title' => 'Invoices',
        'invoice' => 'Invoice',
        'date' => 'Date',
        'status' => 'Status',
        'total' => 'Total',
        'statuses' => [
            'draft' => 'Draft',
            'open' => 'Open',
            'paid' => 'Paid',
            'uncollectible' => 'Uncollectible',
            'void' => 'Void',
        ],
        'empty' => 'No invoices yet.',
    ],

    'refresh' => [
        'found' => 'Subscription confirmed. Access unlocked!',
        'not_found' => 'No active subscription found on Stripe.',
        'failed' => 'We could not reach Stripe right now. Try again shortly.',
        'action' => 'I already paid, refresh status',
    ],
];
