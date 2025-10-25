<?php
declare(strict_types=1);

namespace Lsr\Logging\Formatter;

use DateTimeInterface;
use Lsr\Logging\Interface\ContextSerializerInterface;
use Lsr\Logging\Interface\LogFormatterInterface;
use Lsr\Logging\LogLevel;

class LsrFormatter implements LogFormatterInterface
{
    /**
     * @param ContextSerializerInterface $contextSerializer
     * @param array<string, mixed> $context Additional static context to include in every log entry ('timestamp', 'severity', 'message' and dynamic 'context' will be overwritten on per-entry basis)
     */
    public function __construct(
        protected readonly ContextSerializerInterface $contextSerializer,
        private readonly array                        $context = []
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function format(string|LogLevel $level, string $message, mixed $context = null): string
    {
        // Extend context with predefined static context
        $context = array_merge($this->context, $this->transformContext($context));
        /** @var non-empty-string $line */
        $line = sprintf(
            '[%s] %s: %s %s',
            date(DateTimeInterface::RFC3339),
            is_string($level) ? $level : $level->value,
            addcslashes($message, "\n"),
            !empty($context)
                ? $this->contextSerializer->serialize($context)
                : ''
        );
        return $line;
    }

    /**
     * @param mixed $context
     * @return array<string,mixed>
     */
    protected function transformContext(mixed $context): array
    {
        if (is_array($context)) {
            /** @phpstan-ignore return.type */
            return $context;
        }

        if (is_object($context)) {
            return get_object_vars($context);
        }

        if (empty($context)) {
            return [];
        }

        return ['value' => $context];
    }

}