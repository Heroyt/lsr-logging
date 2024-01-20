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
use Lsr\Logging\Exceptions\DirectoryCreationException;
use Psr\Log\AbstractLogger;
use Psr\Log\InvalidArgumentException;
use Throwable;

/**
 * Class Logger
 *
 * @package eSoul\Logging
 */
class Logger extends AbstractLogger
{

	protected readonly string $file;

	private readonly FsHelper $fsHelper;
	private bool $dirCreated = false;

	/**
	 * Logger constructor.
	 *
	 * @param string $path     Path, where the log file should be created
	 * @param string $fileName Logging name (without extension)
	 *
	 * @throws DirectoryCreationException
	 */
	public function __construct(private string $path, private readonly string $fileName = 'logging') {
		$this->fsHelper = FsHelper::getInstance();

		if (!str_ends_with($this->path, '/')) {
			$this->path .= '/';
		}

		$this->file = $this->path.$this->fileName.'-'.date('Y-m-d').'.log';
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
		// Create (check) directory only on first write
		// This may optimize the constructor a bit if the Logger is initialized, but no logs are written.
		if (!$this->dirCreated) {
			$dirs = $this->fsHelper->extractPath($this->path);
			$this->fsHelper->createDirRecursive($dirs);
			$this->dirCreated = true;
		}

		// Check log file
		if (!file_exists($this->file)) {
			touch($this->file); // Create file
		}
		if (!is_writable($this->file)) {
			chmod($this->file, 0777);
		}

		$contextFormatted = '';
		if (!empty($context)) {
			try {
				$contextFormatted = ' '.json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
			} catch (JsonException) {
			}
		}
		file_put_contents($this->file, sprintf('[%s] %s: %s'.$contextFormatted.PHP_EOL, date('Y-m-d H:i:s'), strtoupper($level), $message), FILE_APPEND);
	}

	/**
	 * Log exception as an error + debug trace
	 *
	 * @param Throwable $exception
	 *
	 * @return void
	 */
	public function exception(Throwable $exception) : void {
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

			$sql = $event->result->getSql();
			if (!empty($sql)) {
				$this->debug('SQL: '.$sql);
			}
		}
		DbTracyPanel::logEvent($logEvent);
	}
}