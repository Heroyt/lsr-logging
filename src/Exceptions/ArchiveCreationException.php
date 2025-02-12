<?php

/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace Lsr\Logging\Exceptions;

use Exception;
use Lsr\Logging\LogArchiver;
use Throwable;
use ZipArchive;

/**
 * Class ArchiveCreationException
 *
 * @package eSoul\Logging
 */
class ArchiveCreationException extends Exception
{
    /** @var string[] */
    public const array MESSAGES = [
        ZipArchive::ER_EXISTS => 'The zip archive already exists.',
        ZipArchive::ER_INCONS => 'The zip archive is inconsistent.',
        ZipArchive::ER_INVAL  => 'Invalid argument supplied to the open method.',
        ZipArchive::ER_MEMORY => 'Error allocating memory.',
        ZipArchive::ER_NOENT  => 'The file does not exist.',
        ZipArchive::ER_NOZIP  => 'The file is not a zip archive.',
        ZipArchive::ER_OPEN   => 'The file could not be opened.',
        ZipArchive::ER_READ   => 'Read error.',
        ZipArchive::ER_SEEK   => 'Seek error.',
        LogArchiver::ER_SAVE  => 'Save error.',
    ];

    public function __construct(int $errorCode, ?Throwable $previous = null) {
        parent::__construct(
            sprintf('Failed creating a log archive: %s', $this::MESSAGES[$errorCode] ?? 'Generic error'),
            $errorCode,
            $previous
        );
    }
}
