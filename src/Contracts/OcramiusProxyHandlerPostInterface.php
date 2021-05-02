<?php

namespace Prokl\CacheProxificator\Contracts;

use Psr\Cache\InvalidArgumentException;

/**
 * Interface OcramiusProxyHandlerPostInterface
 * @package Prokl\CacheProxificator\Contracts
 *
 * @since 01.05.2021
 */
interface OcramiusProxyHandlerPostInterface
{
    /**
     * @var object  $proxy       The proxy that intercepted the method call.
     * @var object  $instance    The wrapped instance within the proxy.
     * @var string  $method      Name of the called method.
     * @var array   $params      Sorted array of parameters passed to the intercepted method, indexed by parameter name.
     * @var mixed  $returnValue  The return value of the intercepted method
     * @var boolean $returnEarly Flag to tell the interceptor proxy to return early, returning
     *                           the interceptor's return value instead of executing the method logic.
     *
     * @return mixed
     * @throws InvalidArgumentException Когда с кэшом что-то не так.
     *
     * @see https://github.com/Ocramius/ProxyManager/blob/2.12.x/docs/access-interceptor-value-holder.md
     */
    public function handler($proxy, $instance, $method, $params, $returnValue, &$returnEarly);
}