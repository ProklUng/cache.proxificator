<?php

namespace Prokl\CacheProxificator;

use Closure;
use Prokl\CacheProxificator\Base\BaseProxificator;
use Prokl\CacheProxificator\Contracts\OcramiusProxyHandlerPostInterface;
use Prokl\CacheProxificator\Contracts\OcramiusProxyHandlerPreInterface;
use ReflectionException;

/**
 * Class ProxificatorAbstraction
 * @package Prokl\CacheProxificator
 */
class ProxificatorAbstraction extends BaseProxificator
{
    /**
     * @var OcramiusProxyHandlerPreInterface $handler Обработчик pre-access interceptor.
     */
    protected $preInterceptor;

    /**
     * @var OcramiusProxyHandlerPostInterface|null $postInterceptor Обработчик post-access interceptor.
     */
    protected $postInterceptor;

    /**
     * ProxificatorAbstraction constructor.
     *
     * @param object                                 $source              Исходный объект.
     * @param ReflectionProcessor                    $reflectionProcessor Процессор рефлексии.
     * @param OcramiusProxyHandlerPreInterface|null  $preInterceptor      Обработчик pre-access interceptor.
     * @param OcramiusProxyHandlerPostInterface|null $postInterceptor     Обработчик post-access interceptor.
     * @param array                                  $filterMethods       Фильтр кэшируемых методов.
     *
     * @throws ReflectionException Когда что-то не так с рефлексией.
     */
    public function __construct(
        $source,
        ReflectionProcessor $reflectionProcessor,
        OcramiusProxyHandlerPreInterface $preInterceptor = null,
        OcramiusProxyHandlerPostInterface $postInterceptor = null,
        array $filterMethods = []
    ) {
        $this->source = $source;
        $this->preInterceptor = $preInterceptor;
        $this->postInterceptor = $postInterceptor;
        $this->reflectionProcessor = $reflectionProcessor;
        $this->filterMethods = $filterMethods;

        $this->createProxy();
    }

    /**
     * Инициализировать обработчик pre-access interceptor.
     *
     * @return void
     */
    protected function initPreInterceptorProxy() : void
    {
        if ($this->preInterceptor !== null) {
            $this->handlerPreInterceptor = Closure::fromCallable([$this->preInterceptor, 'handler']);
        }
    }

    /**
     * Инициализировать обработчик pre-access interceptor.
     *
     * @return void
     */
    protected function initPostInterceptorProxy() : void
    {
        if ($this->postInterceptor !== null) {
            $this->handlerPostInterceptor = Closure::fromCallable([$this->postInterceptor, 'handler']);
        }
    }
}
