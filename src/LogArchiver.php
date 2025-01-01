<?php

declare(strict_types=1);

namespace Lsr\Logging;

use Lsr\Logging\Exceptions\ArchiveCreationException;
use ZipArchive;

class LogArchiver
{
    public const int ER_SAVE = 99;

    private readonly int $maxLogTime;

    public function __construct(
        private readonly FsHelper $fsHelper,
        public readonly string    $maxLogLife = '-2 days',
    ) {
        $timestamp = strtotime($this->maxLogLife);
        assert(is_int($timestamp));
        $this->maxLogTime = $timestamp;
    }

    /**
     * Archive old log files
     *
     * @param  string  $path
     * @param  string  $fileName
     * @param  string|null  $archiveDir
     * @return string[]|null Array of formatted weeks that will be archived or null if no archive log files are found
     * @throws ArchiveCreationException
     */
    public function archiveOld(string $path, string $fileName, ?string $archiveDir = null): ?array {
        $path = trailingSlashIt($path);
        $files = glob($path . $fileName . '-*.log');
        if (empty($files)) {
            return null;
        }
        $archiveFiles = [];
        foreach ($files as $file) {
            $date = strtotime(str_replace([$path . $fileName . '-', '.log'], '', $file));
            if ($date !== false && $date < $this->maxLogTime) {
                $week = date('Y-m-W', $date);
                $archiveFiles[$week] ??= [];
                $archiveFiles[$week][] = $file;
            }
        }

        // Default to the same path as the log files
        if (!isset($archiveDir)) {
            $archiveDir = $path;
        } else if ($archiveDir[0] !== '/') {
            $archiveDir = $path . $archiveDir;
        }

        if (!str_ends_with($archiveDir, '/')) {
            $archiveDir .= '/';
        }

        // Maybe create archive directory
        $dirs = $this->fsHelper->extractPath($archiveDir);
        $this->fsHelper->createDirRecursive($dirs);

        if (count($archiveFiles) > 0) {
            foreach ($archiveFiles as $week => $files) {
                // Get or create zip archive of the logs
                $archive = new ZipArchive();
                $test = $archive->open(
                    $archiveDir . $fileName . '-' . $week . '.zip',
                    ZipArchive::CREATE
                ); // Create or open a zip file
                if ($test !== true) {
                    throw new ArchiveCreationException($test);
                }
                foreach ($files as $file) {
                    $archive->addFile($file, str_replace($path, '', $file));
                }
                if (!@$archive->close()) {
                    throw new ArchiveCreationException($this::ER_SAVE);
                }

                // Remove files after successful compression
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
        return array_keys($archiveFiles);
    }
}
