<?php

/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace Lsr\Logging\Exceptions;

use Throwable;

/**
 * Class DirectoryCreationException
 *
 * @package eSoul\Logging
 */
class DirectoryCreationException extends FileSystemException
{
    public function __construct(string $path, ?Throwable $previous = null) {
        parent::__construct($path, sprintf('Failed creating logging directory: %s', $path), $previous);
    }
}
