<?php

return [
    'title' => 'Contacts',
    'subtitle' => 'Suppliers and clients used in your transactions.',
    'empty' => 'No contacts yet.',
    'add' => 'Add contact',

    'table' => [
        'name' => 'Name',
        'type' => 'Type',
        'document' => 'Document',
        'contact' => 'Contact',
    ],

    'actions' => [
        'edit' => 'Edit contact',
        'delete' => 'Delete contact',
    ],

    'delete_confirm' => 'Delete the contact “:name”? This action cannot be undone.',

    'modal' => [
        'create_title' => 'New Contact',
        'edit_title' => 'Edit Contact',
        'description' => 'Register a supplier or client.',
    ],

    'fields' => [
        'name' => 'Name',
        'name_placeholder' => 'Contact name',
        'type' => 'Type',
        'type_placeholder' => 'Select',
        'document' => 'Document',
        'document_placeholder' => 'Tax ID',
        'email' => 'Email',
        'email_placeholder' => 'email@example.com',
        'phone' => 'Phone',
        'notes' => 'Notes',
        'notes_placeholder' => 'Notes about the contact',
    ],

    'phone' => [
        'select_country' => 'Select country',
    ],
];
