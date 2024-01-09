<?php

namespace Lsr\Logging;

use Lsr\Logging\Exceptions\DirectoryCreationException;

class FsHelper
{

	private static FsHelper $instance;

	/** @var string[] */
	public readonly array $baseDir;
	public string $basePath = '';

	public function __construct() {
		/** @var false|string $baseDir */
		$baseDir = ini_get('open_basedir');
		if ($baseDir !== false) {
			$dirs = explode(':', $baseDir);
			$this->basePath = '';
			$this->baseDir = array_filter(
				explode(DIRECTORY_SEPARATOR, $dirs[0]),
				static fn($dir) => !empty($dir) && $dir !== '.'
			);
		}
		else {
			$this->baseDir = [];
		}
	}

	public static function getInstance() : FsHelper {
		self::$instance = new self;
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
	public function createDirRecursive(string &$directory, array &$path, ?array $baseDir = null) : void {
		$baseDir ??= $this->baseDir;
		if (count($baseDir) > 0) {
			$this->basePath .= '/'.array_shift($baseDir);
		}
		if ($this->basePath !== $directory && !file_exists($directory) && !mkdir($directory) && !is_dir($directory)) {
			throw new DirectoryCreationException($directory);
		}
		if (count($path) > 0) {
			$directory .= '/'.array_shift($path);
			$this->createDirRecursive($directory, $path, $baseDir);
		}
	}

	/**
	 * @param string $path
	 * @return string[]
	 */
	public function extractPath(string $path) : array {
		return array_filter(explode(DIRECTORY_SEPARATOR, $path), static fn($dir) => !empty($dir));
	}

	/**
	 * @param string[] $path
	 * @param bool     $absolute
	 * @return string
	 */
	public function joinPath(array $path, bool $absolute = true) : string {
		$directory = '';
		$dir = array_shift($path);
		$directory .= $dir;
		while ($dir === '..') {
			$dir = array_shift($path);
			$directory .= '/'.$dir;
		}
		return ($absolute && !$this->checkWinPath($directory) ? '/' : '').$directory;
	}

	/**
	 * Checks if path is a Windows absolute path
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function checkWinPath(string $path) : bool {
		return DIRECTORY_SEPARATOR === '\\' && preg_match('/([A-Z]:)/', substr($path, 0, 2)) === 1;
	}

}