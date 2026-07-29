<?php

return [
    'title' => 'Services',
    'subtitle' => 'What you sell, with the price used as a starting point on entries.',
    'add' => 'Add service',
    'empty' => 'No services yet.',
    'delete_confirm' => 'Delete “:name”? Entries keep their amount but stop being attributed to it.',
    'no_price' => 'No standing price',
    'columns' => [
        'name' => 'Name',
        'default_price' => 'Standing price',
    ],
    'modal' => [
        'create_title' => 'New Service',
        'edit_title' => 'Edit Service',
        'description' => 'The standing price is only a suggestion when adding a line. Changing it never rewrites what is already recorded.',
        'name_label' => 'Name',
        'name_placeholder' => 'e.g. Hosting',
        'price_label' => 'Standing price',
        'price_hint' => 'Optional. Leave blank for work quoted per project.',
        'currency_label' => 'Currency',
        'color_label' => 'Color',
    ],
];
