<?php
/**
 * @author Tomáš Vojík <xvojik00@stud.fit.vutbr.cz>, <vojik@wboy.cz>
 */

namespace Lsr\Logging\Exceptions;

use Exception;
use Throwable;

/**
 * Class DirectoryCreationException
 *
 * @package eSoul\Logging
 */
class DirectoryCreationException extends Exception
{

	public function __construct(string $path, Throwable $previous = null) {
		parent::__construct(sprintf('Failed creating logging directory: %s', $path), 0, $previous);
	}
}