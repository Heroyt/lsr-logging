<?php
declare(strict_types=1);

namespace Lsr\Logging;

enum LogLevel: string
{

    case DEBUG = 'DEBUG';
    case INFO = 'INFO';
    case NOTICE = 'NOTICE';
    case WARNING = 'WARNING';
    case ERROR = 'ERROR';
    case CRITICAL = 'CRITICAL';
    case ALERT = 'ALERT';
    case EMERGENCY = 'EMERGENCY';

    public function syslogValue(): int
    {
        return match ($this) {
            self::DEBUG => 7,
            self::INFO => 6,
            self::NOTICE => 5,
            self::WARNING => 4,
            self::ERROR => 3,
            self::CRITICAL => 2,
            self::ALERT => 1,
            self::EMERGENCY => 0,
        };
    }

    public function syslogString(): string
    {
        return match ($this) {
            self::DEBUG => 'debug',
            self::INFO => 'info',
            self::NOTICE => 'notice',
            self::WARNING => 'warning',
            self::ERROR => 'err',
            self::CRITICAL => 'crit',
            self::ALERT => 'alert',
            self::EMERGENCY => 'emerg',
        };
    }

}
