<?php

namespace Lsr\Logging;

use Lsr\Logging\Exceptions\DirectoryCreationException;

class FsHelper
{
    private static FsHelper $instance;

    /** @var string[] */
    public readonly array $baseDir;

    /**
     * @param non-empty-string $directorySeparator
     */
    public function __construct(
        private readonly string $directorySeparator = DIRECTORY_SEPARATOR
    ) {
        /** @var false|string $baseDir */
        $baseDir = ini_get('open_basedir');
        if ($baseDir !== false) {
            $dirs = explode(':', $baseDir);
            $this->baseDir = array_filter(
                explode($this->directorySeparator, $dirs[0]),
                static fn($dir) => !empty($dir) && $dir !== '.'
            );
        } else {
            $this->baseDir = [];
        }
    }

    public static function getInstance(): FsHelper {
        self::$instance ??= new self();
        return self::$instance;
    }

    /**
     * Create a directory structure to
     *
     * @param string        $directory Current directory path
     * @param string[]      $path      Remaining subdirectories
     * @param string[]|null $baseDir
     *
     * @throws DirectoryCreationException
     */
    public function createDirRecursive(array $path, string $directory = '', ?array $baseDir = null, string $basePath = ''): void {
        if (empty($directory)) {
            $directory .= '/' . array_shift($path);
        }
        $baseDir ??= $this->baseDir;
        if (count($baseDir) > 0) {
            $basePath .= '/' . array_shift($baseDir);
        }
        if ($basePath !== $directory && !file_exists($directory) && @!mkdir($directory) && !is_dir($directory)) {
            throw new DirectoryCreationException($directory);
        }
        if (count($path) > 0) {
            $directory .= '/' . array_shift($path);
            $this->createDirRecursive($path, $directory, $baseDir, $basePath);
        }
    }

    /**
     * @param string $path
     * @return string[]
     */
    public function extractPath(string $path): array {
        return array_values(
            array_filter(
                explode(
                    $this->directorySeparator,
                    $path
                ),
                static fn($dir) => !empty($dir)
            )
        );
    }

    /**
     * @param string[] $path
     * @param bool     $absolute
     * @return string
     */
    public function joinPath(array $path, bool $absolute = true): string {
        // Pre-scan path
        $filtered = [];
        foreach ($path as $dir) {
            $dir = trim($dir);
            if ($dir === '') {
                continue;
            }
            if ($dir === '..' && count($filtered) > 0) {
                array_pop($filtered);
                continue;
            }
            $filtered[] = $dir;
        }

        $directory = implode($this->directorySeparator, $filtered);
        return ($absolute && !$this->checkWinPath($directory) ? '/' : '') . $directory;
    }

    /**
     * Checks if path is a Windows absolute path
     *
     * @param string $path
     *
     * @return bool
     */
    public function checkWinPath(string $path): bool {
        return $this->directorySeparator === '\\' && preg_match('/([A-Z]:)/', substr($path, 0, 2)) === 1;
    }
}
