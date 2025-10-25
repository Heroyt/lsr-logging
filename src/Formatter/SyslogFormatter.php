<?php
declare(strict_types=1);

namespace Lsr\Logging\Formatter;

use DateTimeInterface;
use Lsr\Logging\ContextExtractor;
use Lsr\Logging\Interface\ContextSerializerInterface;
use Lsr\Logging\Interface\LogFormatterInterface;
use Lsr\Logging\Logger;
use Lsr\Logging\LogLevel;

class SyslogFormatter implements LogFormatterInterface
{
    use ContextExtractor;

    protected const int FACILITY = 1; // user-level messages
    protected const int SYSLOG_VERSION = 1;
    protected const string NILVALUE = '-';

    public function __construct(
        protected readonly ContextSerializerInterface $contextSerializer,
        protected readonly ?string                    $hostname = null,
        protected readonly ?string                    $appName = null,
        protected readonly ?int                       $procId = null,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function format(string|LogLevel $level, string $message, mixed $context = null): string
    {
        if (is_string($level)) {
            $level = LogLevel::tryFrom($level) ?? LogLevel::INFO;
        }

        $msgId = $this->getContextValue($context, Logger::CHANNEL, null);
        if ($msgId === null) {
            $msgId = $this::NILVALUE;
        } else {
            $this->removeContextValue($context, Logger::CHANNEL);
            if (!is_string($msgId) || $msgId === '') {
                $msgId = $this::NILVALUE;
            }
        }

        $structuredData = $this::NILVALUE;
        if (!empty($context)) {
            try {
                $structuredData = $this->contextSerializer->serialize($context);
            } catch (\Throwable) { // Might throw JsonException or others
                // ignore serialization errors, leave structuredData as NILVALUE
            }
        }

        /** @var non-empty-string $line */
        $line = sprintf(
            '<%d>%d %s %s %s %s %s %s %s: %s',
            $this->calculatePriValue($level),
            $this::SYSLOG_VERSION,
            date(DateTimeInterface::RFC3339),
            $this->hostname ?? $this::NILVALUE,
            $this->appName ?? $this::NILVALUE,
            (string)($this->procId ?? $this::NILVALUE),
            $msgId,
            $structuredData,
            $level->syslogString(),
            addcslashes($message, "\n")
        );
        return $line;
    }

    protected function calculatePriValue(LogLevel $level): int
    {
        return ($this::FACILITY * 8) + $level->syslogValue();
    }
}