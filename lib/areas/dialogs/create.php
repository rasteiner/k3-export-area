<?php 

namespace rasteiner\export;

return [
    'pattern' => 'export/create',
    'load' => function () {
        $basePath = option('rasteiner.export.base', '/');

        if(is_callable($basePath)) {
            $basePath = $basePath();
        }

        if(is_array($basePath)) {
            $options = [];
            foreach ($basePath as $value => $text) {
                if(is_int($value)) {
                    $value = $text;
                    $text = $text;
                }
                $options[] = [
                    'value' => $value,
                    'text' => $text
                ];
            }
        } else if(is_string($basePath)) {
            $options = [
                [
                    'value' => $basePath,
                    'text' => $basePath
                ]
            ];
        } else {
            $options = [];
        }

        return [
            'component' => 'k-form-dialog',
            'props' => [
                'novalidate' => false,
                'value' => [
                    'basePath' => $options[0]['value'],
                ],
                'fields' => [ 
                    "recreateImages" => [
                        "type" => "toggle",
                        "label" => "Recreate images",
                        "help" => "Forces the recreation of images even if they are already cached. This could be much slower.",
                        "value" => false,
                        "text" => [
                            "Use cached images",
                            "Recreate images"
                        ]
                    ],
                    "basePath" => [
                        "type" => "select",
                        "label" => "Base path",
                        "help" => "The base path for the export. URLs in the export will be relative to this path.",
                        "required" => true,
                        "default" => $options[0]['value'],
                        "options" => $options
                    ],
                ],
            ]
        ];
    },
    'submit' => function () {
        return ['miao' => get()];
    }
];