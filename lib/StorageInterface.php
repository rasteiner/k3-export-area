<?php 

namespace rasteiner\export;

use Generator;

interface StorageInterface {
    public function __construct(string $id);
    public function addString(string $filepath, string $content): void;
    public function addFile(string $target, string $source): void;
    public function download(): void;
    public function open(): void;
    public function close(?callable $onProgress = null): void;
    public function size(): int;
    public function delete(): void;
    public function date() : string;
    public function setBasePath(string $basePath) : void;

    public function unpackTo(string $path): void;

    public static function get(string $id): ?StorageInterface;
    public static function list(): array;

}