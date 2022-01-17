<?php 

use Kirby\Cms\App as Kirby;
use Kirby\Cms\App;
use Kirby\Filesystem\F;
use rasteiner\export\Exporter;

load([
    'rasteiner\export\StorageInterface' => 'StorageInterface.php',
    'rasteiner\export\Storage' => 'Storage.php',
    'rasteiner\export\ZipStorage' => 'ZipStorage.php',
    'rasteiner\export\Exporter' => 'Exporter.php',
], __DIR__ . '/lib');

Kirby::plugin('rasteiner/export', [
    'areas' => [
        'export' => __DIR__ . '/lib/areas/export.php',
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'exports',
                'method' => 'POST',
                'action' => function() {
                    set_time_limit(0);

                    Exporter::create(get() + [
                        'channel' => function($cmd, $value) {
                            echo $cmd . ': ' . $value . "\n";
                            flush();
                        }
                    ]);

                    exit;
                }
            ],
            [
                'pattern' => 'exports/(:any)/download',
                'method' => 'GET',
                'auth' => false,
                'action' => function(string $id) {
                    //manually check if the user is allowed to download the export
                    $user = App::instance()->user();
                    if(!$user || !$user->isAdmin()) {
                        throw new \Exception('You are not allowed to download this export');
                    }
                    Exporter::download($id) and exit;
                }
            ]
        ]
    ],
]);