<?php

namespace Cego;

use Exception;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Handler\StreamHandler;

class RotatingFileStreamHandler extends StreamHandler
{
    private string $filename;
    private int $maxFiles;
    private int $maxFileSize;
    private bool $mustRotate = false;

    public function __construct(string $filename, int $maxFileSize, int $maxFiles, $level = Level::Debug, $bubble = true, $filePermission = null, $useLocking = false)
    {
        parent::__construct($filename, $level, $bubble, $filePermission, $useLocking);

        $this->filename = $filename;
        $this->maxFiles = $maxFiles;
        $this->maxFileSize = $maxFileSize;

        if ($this->maxFiles <= 0) {
            throw new Exception('maxFiles must be larger than 0');
        }

        if ($this->maxFileSize <= 0) {
            throw new Exception('maxFileSize must be larger than 0');
        }
    }

    public function close(): void
    {
        parent::close();

        if ($this->mustRotate) {
            $this->rotate();
        }
    }

    public function reset(): void
    {
        parent::reset();

        if ($this->mustRotate) {
            $this->rotate();
        }
    }

    protected function write(LogRecord $record): void
    {
        clearstatcache(true, $this->filename);

        if (file_exists($this->filename)) {
            $fileSize = filesize($this->filename);

            if ($fileSize >= $this->maxFileSize) {
                $this->mustRotate = true;
                $this->close();
            }
        }

        parent::write($record);
    }

    private function rotationFilepath(string $filename, int $number): string
    {
        $info = pathinfo($filename);
        $dirname = isset($info['dirname']) ? $info['dirname'] . '/' : '';
        $filename = $info['filename'];
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

        return "$dirname$filename.$number$extension";
    }

    protected function rotate(): void
    {
        if ($this->maxFileSize === 0) {
            return;
        }

        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $source = $this->rotationFilepath($this->filename, $i);
            clearstatcache(true, $source);

            if (file_exists($source)) {
                $target = $this->rotationFilepath($this->filename, ($i + 1));
                rename($source, $target);
            }
        }

        clearstatcache(true, $this->filename);

        if (file_exists($this->filename)) {
            $target = $this->rotationFilepath($this->filename, 1);
            rename($this->filename, $target);
        }

        $this->mustRotate = false;
    }
}
