<?php
declare(strict_types=1);

namespace Lsr\Logging\ContextSerializer;

use Lsr\Logging\Interface\ContextSerializerInterface;

class SyslogStructuredDataSerializer implements ContextSerializerInterface
{

    public function serialize(mixed $context): string
    {
        if (empty($context)) {
            return '';
        }

        if (is_scalar($context)) {
            return '[DATA value="' . addcslashes((string)$context, '"\\') . '"]';
        }

        if (is_object($context)) {
            $context = get_object_vars($context); // Convert to array
        }

        if (!is_array($context)) {
            return '[DATA value="' . addcslashes(json_encode($context, JSON_THROW_ON_ERROR), '"\\') . '"]';
        }

        $pairsFormatted = [];
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $value = addcslashes((string)$value, '"\\');
            } else {
                $value = addcslashes(json_encode($value, JSON_THROW_ON_ERROR), '"\\');
            }

            $pairsFormatted[] = $key . '="' . $value . '"';
        }

        return '[CONTEXT ' . implode(' ', $pairsFormatted) . ']';
    }
}