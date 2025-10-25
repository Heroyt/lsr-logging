<?php
declare(strict_types=1);

namespace Lsr\Logging\Storage;

use Lsr\Logging\Exceptions\FileSystemException;
use Lsr\Logging\Interface\LogFormatterInterface;

/**
 * Storage that saves logs into s single file and rotates its content (removes old entries) based on size
 */
class RotatingFileStorage extends SimpleFileStorage
{

    public function __construct(
        string                $pathname,
        LogFormatterInterface $formatter,
        protected int         $maxFileSize = 5 * 1024 * 1024, // 5 MB
    )
    {
        parent::__construct($pathname, $formatter);
    }

    protected function writeLogEntry(string $entry): void
    {
        $entrySize = strlen($entry) + 1; // +1 for newline

        // Check file size and truncate if necessary
        assert(file_exists($this->pathname));

        $fileSize = filesize($this->pathname);
        if ($fileSize === false) {
            throw new FileSystemException($this->pathname, 'Unable to get log file size');
        }
        if (($fileSize + $entrySize) > $this->maxFileSize) {
            // Read all lines into an array
            /** @var list<string>|false $lines */
            $lines = file($this->pathname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                throw new FileSystemException($this->pathname, 'Unable to read log file for rotation');
            }

            // Remove lines from the start until the size is under the limit
            do {
                /** @var string $removedLine */
                $removedLine = array_shift($lines);
                $fileSize -= (strlen($removedLine) + 1); // +1 for newline
            } while (($fileSize + $entrySize) > $this->maxFileSize);

            // Add entry line
            $lines[] = $entry;
            // Write back the remaining lines
            if (file_put_contents($this->pathname, implode("\n", $lines) . "\n", LOCK_EX) === false) {
                throw new FileSystemException($this->pathname, 'Unable to write to log file');
            }
            return;
        }

        // If size is under limit, just append the entry
        parent::writeLogEntry($entry);
    }

}