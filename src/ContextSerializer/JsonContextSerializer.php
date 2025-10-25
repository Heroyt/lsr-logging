<?php
declare(strict_types=1);

namespace Lsr\Logging\ContextSerializer;

use Lsr\Logging\Interface\ContextSerializerInterface;

class JsonContextSerializer implements ContextSerializerInterface
{

    public function serialize(mixed $context): string
    {
        return json_encode($context, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}