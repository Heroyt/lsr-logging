<?php

/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace Lsr\Logging\Exceptions;

use RuntimeException;
use Throwable;

/**
 * @package eSoul\Logging
 */
class FileSystemException extends RuntimeException
{
    public function __construct(
        public readonly string $path,
        string                 $message,
        ?Throwable             $previous = null
    )
    {
        parent::__construct($message, 0, $previous);
    }
}
