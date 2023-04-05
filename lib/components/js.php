<?php 

namespace rasteiner\export;

use Kirby\Cms\App;
use Kirby\Filesystem\F;
use Kirby\Http\Url;

return function (StorageInterface $storage) {
    $exported = [];

    return function (App $kirby, string $url, ?array $options = null) use ($storage, &$exported) {
        $original = $kirby->nativeComponent('js');
        if(!isset($exported[$url])) {
            $url = $original($kirby, $url, $options);

            $siteUrl = site()->url();
            $abs = Url::makeAbsolute($url, $siteUrl);
            $relative = substr($abs, strlen($siteUrl));
            $file = $kirby->roots()->index() . $relative;

            $hash = substr(md5_file($file), 0, 8);
            $hashedFilename = F::name($file) . ".$hash.js";
            $path = ltrim(dirname($relative), '/') . '/' . $hashedFilename;
            $abs = dirname($abs) . '/' . $hashedFilename;
            
            $exported[$url] = $abs;
            $storage->addFile($path, $file);
        }
        return $exported[$url];
    };
};