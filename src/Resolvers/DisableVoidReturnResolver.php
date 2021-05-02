<?php

namespace Prokl\CacheProxificator\Resolvers;

use Prokl\CacheProxificator\Contracts\MethodResolverInterface;
use ReflectionMethod;

/**
 * Class DisableVoidReturnResolver
 * Запрет обрабатывать методы, возвращающие void или null.
 * @package Prokl\CacheProxificator\Examples
 *
 * @since 01.05.2021
 */
class DisableVoidReturnResolver implements MethodResolverInterface
{
    /**
     * @inheritDoc
     */
    public function supply(ReflectionMethod $reflectionMethod): bool
    {
        $returnType = $reflectionMethod->getReturnType();
        if ($returnType === null) {
            return false;
        }

        if ($returnType->getName() === 'void' || $returnType->getName() === 'null') {
            return false;
        }

        return true;
    }
}