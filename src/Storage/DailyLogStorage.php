<?php
declare(strict_types=1);

namespace Lsr\Logging\Storage;


use Lsr\Logging\Interface\LogFormatterInterface;

class DailyLogStorage extends SimpleFileStorage
{

    public function __construct(
        protected string      $directory,
        protected string      $logName,
        LogFormatterInterface $formatter,
    )
    {
        $pathname = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $logName . '-' . date('Y-m-d') . '.log';
        parent::__construct($pathname, $formatter);
    }

}