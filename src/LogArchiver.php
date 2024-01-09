<?php

namespace Lsr\Logging;

use Lsr\Logging\Exceptions\ArchiveCreationException;
use ZipArchive;

class LogArchiver
{

	private readonly int $maxLogTime;

	public function __construct(
		private readonly FsHelper $fsHelper,
		public readonly string    $maxLogLife = '-2 days',
	) {
		$this->maxLogTime = strtotime($this->maxLogLife);
	}

	/**
	 * Archive old log files
	 *
	 * @param string      $path
	 * @param string      $fileName
	 * @param string|null $archiveDir
	 * @throws ArchiveCreationException
	 */
	protected function archiveOld(string $path, string $fileName, ?string $archiveDir = null) : void {
		/** @var string[]|false $files */
		$files = glob($path.$fileName.'-*.log');
		if ($files === false) {
			$files = [];
		}
		$archiveFiles = [];
		foreach ($files as $file) {
			$date = strtotime(str_replace([$path.$fileName.'-', '.log'], '', $file));
			if ($date < $this->maxLogTime) {
				$archiveFiles[] = $file;
			}
		}

		// Default to the same path as the log files
		$archiveDir ??= $path;

		// Maybe create archive directory
		$dirs = $this->fsHelper->extractPath($archiveDir);
		$archiveDir = $this->fsHelper->joinPath($dirs);
		$this->fsHelper->createDirRecursive($archiveDir, $dirs);

		if (count($archiveFiles) > 0) {
			// Get or create zip archive of the logs
			$archive = new ZipArchive();
			$test = $archive->open($archiveDir.$fileName.'-'.date('Y-m-W').'.zip', ZipArchive::CREATE); // Create or open a zip file
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

}