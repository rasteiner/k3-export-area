<?php 

namespace rasteiner\export;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Asset;
use Kirby\Cms\FileVersion;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;

return function(StorageInterface $storage, array $creationOptions = []) {
    return function (App $kirby, File|Asset $file, array $options = []) use ($storage, $creationOptions) {
        $createImages = $creationOptions['recreateImages'] ?? option('rasteiner.export.recreateImages') ?? false;

        /**
         * @var FileVersion $version
         */
        $version = $kirby->nativeComponent('file::version')($kirby, $file, $options);
        try {
            if(!$version->exists() || $createImages) {
                $version->save();
            }
        } catch (\Exception $e) {
            if(function_exists('ray')) {
                ray($e);
            }
        }

        $path = Str::substr(F::relativePath($version->root(), $kirby->root()), 1);
        $storage->addFile($path, $version->root());
        return $version;
    };
};