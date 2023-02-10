<?php

namespace Cego;

use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RotatingFileHandler extends StreamHandler
{
    /** @var string */
    protected $filename;

    /** @var number */
    protected $maxFiles;

    /** @var number */
    protected $maxFileSize;

    /** @var boolean */
    protected $mustRotate;

    /**
     * @param string $filename
     * @param int $maxFiles
     * @param int $maxFileSize
     * @param mixed $level
     * @param bool $bubble
     * @param int|null $filePermission
     * @param bool $useLocking
     *
     * @throws Exception
     */
    public function __construct($filename, $maxFiles, $maxFileSize, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        parent::__construct($filename, $level, $bubble, $filePermission, $useLocking);

        $this->filename = $filename;
        $this->maxFiles = (int)$maxFiles;
        $this->maxFileSize = (int)$maxFileSize;

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

    public function reset()
    {
        parent::reset();

        if ($this->mustRotate) {
            $this->rotate();
        }
    }

    protected function write(array $record): void
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

    /**
     * Rotates the files.
     *
     * @return void
     */
    protected function rotate()
    {
        if ($this->maxFileSize === 0) {
            return;
        }

        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $source = $this->filename . '.' . $i;
            clearstatcache(true, $source);

            if (file_exists($source)) {
                $target = $this->filename . '.' . ($i + 1);

                rename($source, $target);
            }
        }

        clearstatcache(true, $this->filename);

        if (file_exists($this->filename)) {
            $target = $this->filename . '.1';

            rename($this->filename, $target);
        }

        $this->mustRotate = false;
    }
}
