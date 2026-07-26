<?php

return [
    'categories' => [
        'title' => 'Categories',
        'subtitle' => 'Income and expense categories.',
        'add' => 'Add category',
        'empty' => 'No categories yet.',
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
        'empty' => 'No tags yet.',
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

    'currencies' => [
        'title' => 'Currencies',
        'subtitle' => 'Enable the currencies you use in accounts and transactions.',
        'activate' => 'Activate :code',
    ],
];
