<?php 

namespace rasteiner\export;

use Generator;

abstract class Storage implements StorageInterface {
    protected $id = null;
    protected $opened = false;
    protected $basePath = null;

    public function __construct(string $id) {
        $this->id = $id;
    }

    public function setBasePath(string $basePath) : void {
        $this->basePath = $basePath;
    }

    public function addFile(string $target, string $source): void {
        $this->addString($target, file_get_contents($source));
    }
    public function open(): void {
        $this->opened = true;
    }
    public function close(?callable $onProgress = null): void {
        $this->opened = false;
        $onProgress && $onProgress(1);
    }
}