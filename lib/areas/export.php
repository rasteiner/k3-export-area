<?php 

namespace rasteiner\export;

return [
    'label' => 'Static Export',
    'icon' => 'download',
    'menu' => true,
    'dialogs' => [
        'export/create' => require __DIR__ . '/dialogs/create.php',
        'export/(:any)/delete' => require __DIR__ . '/dialogs/delete.php',
    ],
    'views' => [
        require __DIR__ . '/views/export.php',
    ]
];