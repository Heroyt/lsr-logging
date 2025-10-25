<?php
declare(strict_types=1);

namespace Lsr\Logging\Formatter;

use Lsr\Logging\Interface\LogFormatterInterface;
use Lsr\Logging\LogLevel;

class JsonLogFormatter implements LogFormatterInterface
{

    /**
     * @param array<string, mixed> $context Additional static context to include in every log entry ('timestamp', 'severity', 'message' and dynamic 'context' will be overwritten on per-entry basis)
     */
    public function __construct(
        private readonly array $context = []
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function format(string|LogLevel $level, string $message, mixed $context = null): string
    {
        $data = $this->context; // Start with predefined context

        $data['timestamp'] = date('c');
        $data['severity'] = is_string($level) ? $level : $level->value;
        $data['message'] = $message;

        if (!empty($context)) {
            $data['context'] = $context;
        }

        return $this->serialize($data);
    }

    /**
     * Function to serialize the formatted data to JSON
     *
     * Extracted to be able to override in subclasses to replace the native json_encode with a custom serializer.
     *
     * @param array<string, mixed> $data
     * @return non-empty-string
     */
    protected function serialize(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}