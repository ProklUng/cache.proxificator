<?php

namespace Prokl\CacheProxificator\Handlers;

use Closure;
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
     * @psalm-suppress MissingParamType
     */
    public function handler($proxy, $instance, $method, $params, &$returnEarly)
    {
        $returnEarly = true;
        $keyCache = $this->getCacheKey(get_class($instance) . (string)$method . $this->implodeRecursive('', $params));

        return $this->cacher->get(
            $keyCache,
            /**
             * @param CacheItemInterface $item
             * @return mixed
             */
            function (CacheItemInterface $item) use ($method, $params, $instance) {
                return $this->reflectionProcessor->invoke($instance, (string)$method, $params);
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
    protected function getCacheKey(string $src): string
    {
        return str_replace(
            ['{', '}', '\\', '@', '/', ':'],
            '',
            $src
        );
    }

    /**
     * Implode multi-dimensional arrays.
     *
     * @param string $separator
     * @param array  $array
     *
     * @return string
     */
    private function implodeRecursive(string $separator, array $array): string
    {
        $string = '';

        /**
         * @var string|int $i
         * @var mixed      $a
         */
        foreach ($array as $i => $a) {
            if (is_array($a)) {
                $string .= $this->implodeRecursive($separator, $a);
            }
            else if (is_object($a) && !$a instanceof Closure) {
                $string .= serialize($a);
            }
            else {
                $string .= (string)$a;
                if ($i < count($array) - 1) {
                    $string .= $separator;
                }
            }
        }

        return $string;
    }
}