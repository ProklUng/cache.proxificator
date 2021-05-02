<?php

namespace Prokl\CacheProxificator;

use Closure;
use Prokl\CacheProxificator\Base\BaseProxificator;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class CacheProxificator
 * @package Prokl\CacheProxificator
 */
class CacheProxificator extends BaseProxificator
{
    /**
     * @var CacheInterface $cacher Кэшер.
     */
    private $cacher;

    /**
     * CacheProxificator constructor.
     *
     * @param object              $source              Исходный объект.
     * @param CacheInterface      $cacher              Кэшер.
     * @param ReflectionProcessor $reflectionProcessor Процессор рефлексии.
     * @param array               $filterMethods       Фильтр кэшируемых методов.
     *
     * @throws ReflectionException Когда что-то не так с рефлексией.
     */
    public function __construct(
        $source,
        CacheInterface $cacher,
        ReflectionProcessor $reflectionProcessor,
        array $filterMethods = []
    ) {
        $this->source = $source;
        $this->cacher = $cacher;
        $this->reflectionProcessor = $reflectionProcessor;
        $this->filterMethods = $filterMethods;

        $this->createProxy();
    }

    /**
     * @var object  $proxy       The proxy that intercepted the method call.
     * @var object  $instance    The wrapped instance within the proxy.
     * @var string  $method      Name of the called method.
     * @var array   $params      Sorted array of parameters passed to the intercepted method, indexed by parameter name.
     * @var boolean $returnEarly Flag to tell the interceptor proxy to return early, returning
     *                           the interceptor's return value instead of executing the method logic.
     *
     * @return mixed
     * @throws InvalidArgumentException Когда с кэшом что-то не так.
     *
     * @see https://github.com/Ocramius/ProxyManager/blob/2.12.x/docs/access-interceptor-value-holder.md
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
     * Инициализировать обработчик pre-access interceptor.
     *
     * @return void
     */
    protected function initPreInterceptorProxy() : void
    {
        $this->handlerPreInterceptor = Closure::fromCallable([$this, 'handler']);
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
