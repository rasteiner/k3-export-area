<?php 

namespace rasteiner\export;
use Kirby\Cms\App;

class Exporter {

    public static function delete(string $id) {
        $export = Exporter::get($id);
        $export->delete();
    }

    public static function storage() : string {
        $storageDriver = option('rasteiner.export.driver', 'rasteiner\export\ZipStorage');
        
        //check if the driver is a valid class
        if (!class_exists($storageDriver)) {
            throw new \Exception('The driver is not a valid class');
        }

        //check if the driver implements the StorageInterface
        if (!is_subclass_of($storageDriver, 'rasteiner\export\StorageInterface')) {
            throw new \Exception('The driver does not implement the StorageInterface');
        }

        return $storageDriver;
    }

    public static function instanceStorage(string $id, string $basePath) : StorageInterface {
        $storageDriver = Exporter::storage();
        $storage = new $storageDriver($id);
        $storage->setBasePath($basePath);
        return $storage;
    }

    public static function get(string $id) {
        $storage = Exporter::storage();
        $export = $storage::get($id);

        if(!$export) {
            throw new \Exception('Export not found');
        }
        return $export;
    }

    public static function download(string $id) : bool {
        //check if $id is valid: only numbers
        if (!preg_match('/^[0-9]+$/', $id)) {
            throw new \Exception('Invalid export id');
        }
        Exporter::get($id)->download();
        return true;
    }

    public static function list() {
        $storage = Exporter::storage();
        return $storage::list();
    }

    public static function create($options = []) {
        $fileVersionComponentFactory = require_once __DIR__ . '/components/file.version.php';
        $fileUrlComponentFactory = require_once __DIR__ . '/components/file.url.php';
        $jsComponentFactory = require_once __DIR__ . '/components/js.php';
        $cssComponentFactory = require_once __DIR__ . '/components/css.php';

        $send = $options['channel'] ?? fn ($cmd, $val) => true;
        
        $id = date('YmdHis');
        $send('id', $id);

        $date = date('c');
        $send('date', $date);

        $urlIndex = $options['basePath'];

        $send('basepath', $urlIndex);

        $storage = Exporter::instanceStorage($id, $urlIndex);
        $storage->open();

        App::plugin('rasteiner/export-components', [
            'components' => [
                'file::version' => $fileVersionComponentFactory($storage, $options),
                'file::url' => $fileUrlComponentFactory($storage, $options),
                'js' => $jsComponentFactory($storage),
                'css' => $cssComponentFactory($storage),
            ],
        ]);


        $newKirby = new App([
            'urls' => [
                'index' => $urlIndex,
            ],
        ], true);

        $newKirby->impersonate('nobody');

        $site = $newKirby->site();
        $pagesRender = option('rasteiner.export.pages', fn () => $site->index());
        $assetsCopy = option('rasteiner.export.assets', fn () => []);
        $stringsCopy = option('rasteiner.export.strings', fn () => []);

        $pages = $pagesRender($newKirby);
        $files = $assetsCopy($newKirby);
        $strings = $stringsCopy($newKirby);

        $fileCount = $pages->count() + count($files) + count($strings);
        $send('count', $fileCount*2);

        $count = 0;
        /**
         * @var \Kirby\Cms\Page $page
         */
        foreach ($pages as $page) {
            $filename = $page->isHomePage() ?
                'index.html' :
                $page->id() . '/index.html';

            $html = $page->render();
            $storage->addString($filename, $html);
            $count++;
            $send('progress', $count);
        }

        foreach ($files as $path => $file) {
            $storage->addFile($path, $file);
            $count++;
            $send('progress', $count);
        }

        foreach ($strings as $dest => $string) {
            $storage->addString($dest, $string);
            $count++;
            $send('progress', $count);
        }
        
        $storage->close(fn($status) => $send('progress', $count + floor($status * $fileCount)));

        $send('size', $storage->size());
    }
}
