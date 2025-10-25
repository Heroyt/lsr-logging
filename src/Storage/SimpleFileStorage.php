<?php
declare(strict_types=1);

namespace Lsr\Logging\Storage;

use Lsr\Logging\Exceptions\DirectoryCreationException;
use Lsr\Logging\Exceptions\FileSystemException;
use Lsr\Logging\Interface\LogFormatterInterface;
use Lsr\Logging\Interface\StorageInterface;
use Lsr\Logging\LogLevel;

/**
 * Log storage class to store logs in a single file
 */
class SimpleFileStorage implements StorageInterface
{

    public function __construct(
        protected readonly string                $pathname,
        protected readonly LogFormatterInterface $formatter,
    )
    {
        // Check if the directory exists, if not create it
        $dir = dirname($this->pathname);
        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            throw new DirectoryCreationException($dir);
        }

        // Make sure that the directory is writable
        if (!is_writable($dir)) {
            throw new FileSystemException($dir, 'Directory is not writable');
        }
    }

    public function store(string|LogLevel $level, string $message, mixed $context = null): void
    {
        // Make sure that the file exists (create if not)
        if (!file_exists($this->pathname) && !touch($this->pathname)) {
            throw new FileSystemException($this->pathname, 'Unable to create log file');
        }

        // Make sure that the file is writable
        if (!is_writable($this->pathname)) {
            throw new FileSystemException($this->pathname, 'Log file is not writable');
        }

        $this->writeLogEntry($this->formatter->format($level, $message, $context));
    }

    /**
     * Commit the log entry to the file
     *
     * @param non-empty-string $entry
     * @return void
     */
    protected function writeLogEntry(string $entry): void
    {
        if (!file_put_contents($this->pathname, $entry . "\n", FILE_APPEND | LOCK_EX)) {
            throw new FileSystemException($this->pathname, 'Unable to write to log file');
        }
    }
}