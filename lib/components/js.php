<?php 

namespace rasteiner\export;

use Kirby\Cms\App;
use Kirby\Filesystem\F;

return function (StorageInterface $storage) {
    $exported = [];

    return function (App $kirby, string $url, ?array $options = null) use ($storage, &$exported) {
        $original = $kirby->nativeComponent('js');
        if(!isset($exported[$url])) {
            $url = $original($kirby, $url, $options);
            $hash = substr(md5_file($kirby->root() . "/$url"), 0, 8);
            $path = dirname($url) . '/' . F::name($url) . ".$hash.js";
            
            $exported[$url] = $path;
            $storage->addFile($path, $kirby->root() . "/$url");
        }
        return $exported[$url];
    };
};