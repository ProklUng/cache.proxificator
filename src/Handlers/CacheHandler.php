<?php

namespace Prokl\CacheProxificator\Handlers;

use Prokl\CacheProxificator\Contracts\OcramiusProxyHandlerPreInterface;
use Prokl\CacheProxificator\ReflectionProcessor;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class CacheHandler
 * @package Prokl\CacheProxificator
 *
 * @since 01.05.2021
 */
class CacheHandler implements OcramiusProxyHandlerPreInterface
{
    /**
     * @var CacheInterface $cacher Кэшер.
     */
    private $cacher;

    /**
     * @var ReflectionProcessor $reflectionProcessor Рефлектор.
     */
    private $reflectionProcessor;

    /**
     * CacheHandler constructor.
     *
     * @param CacheInterface      $cacher              Кэшер.
     * @param ReflectionProcessor $reflectionProcessor Рефлектор.
     */
    public function __construct(
        CacheInterface $cacher,
        ReflectionProcessor $reflectionProcessor
    ) {
        $this->cacher = $cacher;
        $this->reflectionProcessor = $reflectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function handler($proxy, $instance, $method, $params, &$returnEarly)
    {
        $returnEarly = true;
        $keyCache = $this->getCacheKey(get_class($instance) . $method . implode('', $params));

        return $this->cacher->get(
            $keyCache,
            /**
             * @param CacheItemInterface $item
             * @return mixed
             */
            function (CacheItemInterface $item) use ($method, $params, $instance) {
                return $this->reflectionProcessor->invoke($instance, $method, $params);
            }
        );
    }

    /**
     * Нормализация ключа кэша.
     *
     * @param string $src Сырой ключ.
     *
     * @return string
     */
    private function getCacheKey(string $src): string
    {
        return str_replace(
            ['{', '}', '\\', '@', '/', ':'],
            '',
            $src
        );
    }
}