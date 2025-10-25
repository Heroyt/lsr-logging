<?php
declare(strict_types=1);

namespace Lsr\Logging\Interface;

use Lsr\Logging\LogLevel;

interface StorageInterface
{

    /**
     * Store a log message with optional context
     *
     * @param non-empty-string|LogLevel $level
     * @param non-empty-string $message
     * @param mixed|null $context
     * @return void
     */
    public function store(string|LogLevel $level, string $message, mixed $context = null): void;

}