<?php
/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace Lsr\Logging;


use dibi;
use Dibi\Event;
use Exception;
use JsonException;
use Lsr\Exceptions\FileException;
use Lsr\Helpers\Tracy\DbTracyPanel;
use Lsr\Helpers\Tracy\Events\DbEvent;
use Lsr\Logging\Exceptions\ArchiveCreationException;
use Lsr\Logging\Exceptions\DirectoryCreationException;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use ZipArchive;

/**
 * Class Logger
 *
 * @package eSoul\Logging
 */
class Logger extends AbstractLogger
{

	public const MAX_LOG_LIFE = '-2 days';
	protected string $file;
	/** @var string[] */
	protected array  $baseDir  = [];
	protected string $basePath = '';

	protected bool $closeHandle = true;

	/**
	 * Logger constructor.
	 *
	 * @param string $path     Path, where the log file should be created
	 * @param string $fileName Logging name (without extension)
	 *
	 * @throws DirectoryCreationException
	 */
	public function __construct(string $path, string $fileName = 'logging') {
		/** @var string|false $baseDir */
		$baseDir = ini_get('open_basedir');
		if ($baseDir !== false) {
			$dirs = explode(':', $baseDir);
			$this->basePath = '';
			$this->baseDir = array_filter(explode(DIRECTORY_SEPARATOR, $dirs[0]), static function($dir) {
				return !empty($dir) && $dir !== '.';
			});
		}

		$directory = '';
		if ($path[0] !== '/' || !$this->checkWinPath($path)) {
			$directory = '/';
		}
		$dirs = array_filter(explode(DIRECTORY_SEPARATOR, $path), static function($dir) {
			return !empty($dir);
		});
		$dir = array_shift($dirs);
		$directory .= $dir;
		while ($dir === '..') {
			$dir = array_shift($dirs);
			$directory .= '/'.$dir;
		}

		$this->createDirRecursive($directory, $dirs);

		if (!str_ends_with($path, '/')) {
			$path .= '/';
		}

		try {
			$this->archiveOld($path, $fileName);
		} catch (ArchiveCreationException $e) {
			file_put_contents($path.'logError-'.date('YmdHis'), 'Log error ('.$e->getCode().'): '.$path.$fileName.PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString());
		}

		$this->file = $path.$fileName.'-'.date('Y-m-d').'.log';
	}

	/**
	 * Checks if path is a Windows absolute path
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	protected function checkWinPath(string $path) : bool {
		return DIRECTORY_SEPARATOR === '\\' && preg_match('/([A-Z]:)/', substr($path, 0, 2)) === 1;
	}

	/**
	 * Create a directory structure to
	 *
	 * @param string   $directory Current directory path
	 * @param string[] $path      Remaining subdirectories
	 *
	 * @throws DirectoryCreationException
	 */
	protected function createDirRecursive(string &$directory, array &$path) : void {
		if (count($this->baseDir) > 0) {
			$this->basePath .= '/'.array_shift($this->baseDir);
		}
		if ($this->basePath !== $directory && !file_exists($directory) && !mkdir($directory) && !is_dir($directory)) {
			throw new DirectoryCreationException($directory);
		}
		if (count($path) > 0) {
			$directory .= '/'.array_shift($path);
			$this->createDirRecursive($directory, $path);
		}
	}

	/**
	 * Archive old log files
	 *
	 * @param string $path
	 * @param string $fileName
	 *
	 * @throws ArchiveCreationException
	 */
	protected function archiveOld(string $path, string $fileName) : void {
		/** @var string[]|false $files */
		$files = glob($path.$fileName.'-*.log');
		if ($files === false) {
			$files = [];
		}
		$archiveFiles = [];
		$maxLife = strtotime($this::MAX_LOG_LIFE);
		foreach ($files as $file) {
			$date = strtotime(str_replace([$path.$fileName.'-', '.log'], '', $file));
			if ($date < $maxLife) {
				$archiveFiles[] = $file;
			}
		}

		if (count($archiveFiles) > 0) {
			$archive = new ZipArchive();
			$test = $archive->open($path.$fileName.'-'.date('Y-m-W').'.zip', ZipArchive::CREATE); // Create or open a zip file
			if ($test !== true) {
				throw new ArchiveCreationException($test);
			}
			foreach ($archiveFiles as $file) {
				$archive->addFile($file, str_replace($path, '', $file));
			}
			$archive->close();

			// Remove files after successful compression
			foreach ($archiveFiles as $file) {
				unlink($file);
			}
		}
	}

	public function keepHandle() : static {
		$this->closeHandle = false;
		return $this;
	}

	public function dontKeepHandle() : static {
		$this->closeHandle = true;
		return $this;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string               $level
	 * @param string               $message
	 * @param array<string, mixed> $context
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException|FileException
	 */
	public function log($level, $message, array $context = []) : void {
		// Check log file
		if (!file_exists($this->file)) {
			touch($this->file);
		}
		if (!is_writable($this->file)) {
			chmod($this->file, 0777);
		}

		$contextFormatted = '';
		if (!empty($context)) {
			try {
				$contextFormatted = ' '.json_encode($contextFormatted, JSON_THROW_ON_ERROR);
			} catch (JsonException) {
			}
		}
		file_put_contents(
			$this->file,
			sprintf('[%s] %s: %s'.$contextFormatted.PHP_EOL, date('Y-m-d H:i:s'), strtoupper($level), $message),
			FILE_APPEND
		);
	}

	/**
	 * Log exception as an error + debug trace
	 *
	 * @param Exception $exception
	 *
	 * @return void
	 */
	public function exception(Exception $exception) : void {
		$this->error('Thrown Exception ('.$exception->getCode().'): '.$exception->getMessage());
		$this->debug($exception->getTraceAsString());
	}

	/**
	 * Log any dibi database event
	 *
	 * @param Event $event
	 *
	 * @return void
	 */
	public function logDb(Event $event) : void {
		// Create tracy log event
		$logEvent = new DbEvent;
		$logEvent->sql = dibi::dump($event->sql, true) ?? '';
		$logEvent->source = str_replace(ROOT, '', implode(':', $event->source));
		$logEvent->time = $event->time;
		$logEvent->count = (int) $event->count;

		// DB query error
		if ($event->result instanceof Exception) {
			$message = $event->result->getMessage();
			if ($code = $event->result->getCode()) {
				$message = '('.$code.') '.$message;
			}
			$logEvent->status = DbEvent::ERROR;
			$logEvent->message = $message;

			// Log to file
			$this->error($message);
		}
		DbTracyPanel::logEvent($logEvent);
	}
}