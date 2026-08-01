<?php

return [
    'title' => 'Admin',

    'nav' => [
        'dashboard' => 'Overview',
        'tenants' => 'Customers',
        'back_to_app' => 'Back to the app',
    ],

    'dashboard' => [
        'title' => 'Overview',
        'subtitle' => 'How the platform is doing.',
        'recent' => 'Recent customers',
    ],

    'stats' => [
        'tenants' => 'Customers',
        'subscribers' => 'Active subscribers',
        'trialing' => 'On trial',
        'revenue' => 'Monthly revenue',
        'revenue_hint' => 'Recurring from active subscriptions, not the total taken to date.',
    ],

    'tenants' => [
        'title' => 'Customers',
        'subtitle' => ':count in total.',
        'search' => 'Search by name or email',
        'empty' => 'No customers found.',
        'back' => 'Back to customers',
        'page' => 'Page :current of :last',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    'columns' => [
        'tenant' => 'Customer',
        'name' => 'Name',
        'email' => 'Email',
        'plan' => 'Plan',
        'status' => 'Status',
        'created_at' => 'Joined',
    ],

    'status' => [
        'subscribed' => 'Subscribed',
        'trialing' => 'On trial',
        'lapsed' => 'Lapsed',
    ],

    'show' => [
        'account' => 'Account',
        'billing' => 'Billing',
        'verified' => 'Email verified',
        'trial_ends_at' => 'Trial until',
        'stripe_id' => 'Stripe ID',
    ],
];
