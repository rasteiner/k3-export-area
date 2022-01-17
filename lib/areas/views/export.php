<?php 

namespace rasteiner\export;

return [
    'pattern' => 'export',
    'action' => function() {
        return [
            'component' => 'rs-export-view',
            'props' => [
                'files' => Exporter::list()
            ]
        ];
    }
];