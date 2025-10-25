<?php
declare(strict_types=1);

namespace Lsr\Logging\Interface;

use Lsr\Logging\LogLevel;

interface LogFormatterInterface
{

    /**
     * Transform log message and context into a formatted string
     *
     * @param non-empty-string|LogLevel $level
     * @param non-empty-string $message
     * @param mixed|null $context
     * @return non-empty-string
     */
    public function format(string|LogLevel $level, string $message, mixed $context = null): string;

}