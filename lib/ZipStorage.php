<?php 

namespace rasteiner\export;

use Generator;
use Kirby\Data\Data;
use Kirby\Toolkit\Str;
use ZipArchive;

class ZipStorage extends Storage {

    protected ZipArchive $zip;
    protected array $meta;

    public function __construct(string $id) {
        parent::__construct($id);
        $this->meta = $this->meta();
    }

    protected function meta() : array {
        if(isset($this->meta)) {
            return $this->meta;
        }

        $path = $this->path() . '.txt';
        if(file_exists($path)) {
            return $this->meta = Data::read($path);
        } else {
            return $this->meta = [
                'date' => file_exists($this->path()) ? date('c', filemtime($this->path())) : date('c'),
            ];
        }
    }

    public function setBasePath(string $basePath) : void {
        parent::setBasePath($basePath);
        $this->meta['basePath'] = $basePath;
    }

    protected static function root() {
        return option('rasteiner.export.root', __DIR__ . '/../exports');
    }

    public function path() {
        return self::root() . '/' . $this->id . '.zip';
    }

    public function open() : void {
        //create exports root folder if it doesn't exist
        if (!file_exists(self::root())) {
            mkdir(self::root(), 0777, true);
        }

        $this->zip = new ZipArchive();
        $openResult = $this->zip->open($this->path(), ZipArchive::OVERWRITE|ZipArchive::CREATE);
        if(true !== $openResult) {
            throw new \Exception('Could not create zip file: Code '. $openResult);
        }
        parent::open();
    }

    public function addString(string $filepath, string $content): void {
        if($this->zip->addFromString($filepath, $content)) {
            return;
        }

        throw new \Exception('Could not add file to zip: '. $this->zip->getStatusString());
    }

    public function addFile(string $target, string $source): void {
        if($this->zip->addFile($source, $target)) {
            return;
        }

        throw new \Exception('Could not add file to zip: '. $this->zip->getStatusString());
    }

    public function close(?callable $onProgress = null) : void {
        $this->zip->registerProgressCallback(0.01, $onProgress);

        $this->zip->close();

        //save metadata as text file alongside the zip file
        $metadata = [
            'date' => $this->meta['date'],
            'basePath' => $this->meta['basePath'],
        ];

        Data::write($this->path() . '.txt', $metadata);

        parent::close();
    }

    public function download(): void {
        $filename = Str::slug(site()->title() . '-' . $this->date()) . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($this->path()));
        readfile($this->path());
    }

    public function size(): int {
        if(!file_exists($this->path())) {
            throw new \Exception("File does not exist", 1);
        }
        return filesize($this->path());
    }

    public function delete(): void {
        if($this->opened) {
            $this->close();
        }
        $path = $this->path();
        if(file_exists($path)) {
            unlink($path);
        }
        if(file_exists($path . '.txt')) {
            unlink($path . '.txt');
        }
    }

    public function date() : string {
        return $this->meta()['date'];
    }

    public function basePath() : string {
        return $this->meta()['basepath'] ?? 'unknown';
    }

    public function unpackTo(string $path): void {
        $this->zip->extractTo($path);
    }

    public static function get(string $id): ?ZipStorage {
        $obj = new ZipStorage($id);
        if(file_exists($obj->path())) {
            return $obj;
        }
        return null;
    }

    public static function list(): array {
        if(!file_exists(self::root())) {
            return [];
        }

        $files = scandir(self::root());
        $list = [];
        foreach($files as $file) {
            if(preg_match('/^(.+)\.zip$/', $file, $matches)) {
                $id = $matches[1];
                $file = self::get($id);
                $list[] = [
                    'id' => $id,
                    'size' => $file->size(),
                    'date' => $file->date(),
                    'basePath' => $file->basePath(),
                ];
            }
        }
        return $list;
    }

}