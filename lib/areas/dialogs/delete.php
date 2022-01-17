<?php

namespace rasteiner\export;

return [
    'pattern' => 'export/(:any)/delete',
    'load' => function (string $id) {
        return [
            'component' => 'k-remove-dialog',
            'props' => [
                'text' => 'Do you really want to delete this export?'
            ]
        ];
    },
    'submit' => function (string $id) {
        Exporter::delete($id);
        return [ 'result' => 'success' ];
    }
];