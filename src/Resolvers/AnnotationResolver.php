<?php

namespace Prokl\CacheProxificator\Resolvers;

use Doctrine\Common\Annotations\Reader;
use Exception;
use Prokl\CacheProxificator\Contracts\MethodResolverInterface;
use Prokl\CacheProxificator\Resolvers\Annotations\Cacheble;
use ReflectionMethod;

/**
 * Class AnnotationResolver
 * @package Prokl\CacheProxificator\Resolvers
 *
 * @since 01.05.2021
 */
class AnnotationResolver implements MethodResolverInterface
{
    /**
     * @var Reader $reader Читатель аннотаций.
     */
    private $reader;

    /**
     * AnnotationResolver constructor.
     *
     * @param Reader $reader Читатель аннотаций.
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @inheritDoc
     */
    public function supply(ReflectionMethod $reflectionMethod): bool
    {
        try {
            /** @var Cacheble $annotation */
            $annotation = $this->reader->getMethodAnnotation(
                $reflectionMethod,
                Cacheble::class
            );

            if ($annotation) {
                return true;
            }
        } catch (Exception $e) {}

        return false;
    }
}
