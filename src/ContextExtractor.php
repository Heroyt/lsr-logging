<?php
declare(strict_types=1);

namespace Lsr\Logging;

trait ContextExtractor
{

    /**
     * Try to extract a value from the context
     *
     * @param mixed $context
     * @param non-empty-string $key
     * @param mixed|null $default
     * @return mixed
     */
    protected function getContextValue(mixed $context, string $key, mixed $default = null): mixed
    {
        if (is_array($context) && array_key_exists($key, $context)) {
            return $context[$key];
        }
        if (is_object($context) && property_exists($context, $key)) {
            return $context->$key;
        }
        return $default;
    }

    /**
     * Remove a value from the context
     *
     * @param mixed $context
     * @param non-empty-string $key
     * @return void
     */
    protected function removeContextValue(mixed &$context, string $key): void
    {
        if (is_array($context) && array_key_exists($key, $context)) {
            unset($context[$key]);
        }
        if (is_object($context) && property_exists($context, $key)) {
            unset($context->$key);
        }
    }

}