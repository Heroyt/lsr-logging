<?php
declare(strict_types=1);

namespace Lsr\Logging\Interface;

interface ContextSerializerInterface
{

    /**
     * Serialize the given context into a string representation
     *
     * @param mixed $context
     * @return string
     */
    public function serialize(mixed $context): string;

}