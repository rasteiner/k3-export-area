<?php 

namespace rasteiner\export;

use Exception;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

return function(StorageInterface $storage, array $creationOptions = []) {
    $copiedFiles = [];

    return function (App $kirby, File $file) use ($storage, $creationOptions, &$copiedFiles) {
        try {
            $root = $file->root();
            $mediaRoot = $file->mediaRoot();

            if($copiedFiles[$root] ?? false) {
                return;
            }

            $storage->addFile(Str::substr(F::relativePath($mediaRoot, $kirby->root()), 1), $root);
            $copiedFiles[$root] = true;
            return $file->mediaUrl();
        } catch (Exception $e) {
            if(function_exists('ray')) {
                ray($e);
            }
        }
    };
};