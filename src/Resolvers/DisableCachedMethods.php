<?php

namespace Prokl\CacheProxificator\Resolvers;

use Prokl\CacheProxificator\Contracts\MethodResolverInterface;
use ReflectionMethod;

/**
 * Class DisableCachedMethods
 * Запрет обрабатывать методы, в названиях которых встречается слово cache.
 * @package Prokl\CacheProxificator\Examples
 *
 * @since 01.05.2021
 */
class DisableCachedMethods implements MethodResolverInterface
{
    /**
     * @inheritDoc
     */
    public function supply(ReflectionMethod $reflectionMethod): bool
    {
        return stripos($reflectionMethod->getName(), 'cache') === false;
    }
}