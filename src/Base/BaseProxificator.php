<?php

namespace Prokl\CacheProxificator\Base;

use Closure;
use Prokl\CacheProxificator\ReflectionProcessor;
use ProxyManager\Configuration;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use ProxyManager\Proxy\AccessInterceptorValueHolderInterface;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;

/**
 * Class BaseProxificator
 * @package Prokl\CacheProxificator\Base
 *
 * @since 01.05.2021
 *
 * @psalm-suppress MissingConstructor
 * @psalm-suppress MixedArgumentTypeCoercion
 */
class BaseProxificator
{
    /**
     * @var string $env Среда разработки, по умолчанию dev. Если prod, то происходит прегенерация прокси-классов.
     */
    protected $env = 'dev';

    /**
     * @var string|null $cacheDir Директория, куда по умолчанию складываются прегенерированные прокси-классы.
     */
    protected $cacheDir = __DIR__.'/generated-cache-dir';

    /**
     * @var object $source Проксируемый объект.
     */
    protected $source;

    /**
     * @var ReflectionProcessor $reflectionProcessor Рефлектор.
     */
    protected $reflectionProcessor;

    /**
     * @var Factory $factory
     */
    protected $factory;

    /**
     * @var AccessInterceptorValueHolderInterface $proxy Экземпляр прокси.
     */
    protected $proxy;

    /**
     * @var array $filterMethods Методы, подлежащие фильтрации.
     */
    protected $filterMethods;

    /**
     * @var Closure|null $handlerPreInterceptor Обработчик pre-access interceptor.
     */
    protected $handlerPreInterceptor;

    /**
     * @var Closure|null $handlerPostInterceptor Обработчик post-access interceptor.
     */
    protected $handlerPostInterceptor;

    /**
     * Инициализировать обработчик pre-access interceptor.
     * Под наследование.
     *
     * @return void
     * @throws RuntimeException
     */
    protected function initPreInterceptorProxy() : void
    {
        throw new RuntimeException('Должно быть реализовано потомками класса.');
    }

    /**
     * Инициализировать обработчик post-access interceptor.
     * Под наследование.
     *
     * @return void
     */
    protected function initPostInterceptorProxy() : void
    {
    }

    /**
     * @param string $name      Метод.
     * @param mixed  $arguments Параметры.
     *
     * @return mixed
     * @throws RuntimeException Когда запрашивается несуществующий метод.
     */
    public function __call(string $name, $arguments)
    {
        if (!method_exists($this->source, $name)) {
            throw new RuntimeException(
                sprintf(
                    'Метод %s не существует.',
                    $name
                )
            );
        }

        return $this->proxy->{$name}(...$arguments);
    }

    /**
     * @param string $method    Метод.
     * @param mixed  ...$params Параметры.
     *
     * @return mixed
     */
    public function proxificate(string $method, ...$params)
    {
        return $this->proxy->{$method}(...$params);
    }

    /**
     * @return AccessInterceptorValueHolderInterface
     */
    public function getProxy(): AccessInterceptorValueHolderInterface
    {
        return $this->proxy;
    }

    /**
     * Создать параметры проксирования класса на все публичные методы (и не запрещенные ресолверами),
     * кроме конструктора.
     *
     * @param Closure|null $handler Обработчик.
     *
     * @return array
     * @throws ReflectionException Когда с рефлексией что-то не заладилось.
     */
    private function createParamProxy(?Closure $handler) : array
    {
        if ($handler === null) {
            return [];
        }

        $result = [];
        $methods = $this->reflectionProcessor->reflectClassMethods($this->source, $this->filterMethods);

        /** @var ReflectionMethod $method */
        foreach ($methods as $method) {
            $name = $method->getName();
            if ($name === '__construct') {
                continue;
            }
            $result[$name] = $this->handlerPreInterceptor;
        }

        return $result;
    }

    /**
     * Создать прокси.
     *
     * @return void
     * @throws ReflectionException Когда что-то не так с рефлексией.
     */
    protected function createProxy(): void
    {
        $config = $this->generateProxyRuntime();

        $this->factory = new Factory($config);

        $this->initPreInterceptorProxy();
        $this->initPostInterceptorProxy();

        $this->proxy = $this->factory->createProxy(
            $this->source,
            $this->createParamProxy($this->handlerPreInterceptor),
            $this->createParamProxy($this->handlerPostInterceptor)
        );
    }

    /**
     * @see https://github.com/Ocramius/ProxyManager/blob/2.12.x/docs/tuning-for-production.md
     *
     * @return Configuration | null
     */
    private function generateProxyRuntime() : ?Configuration
    {
        if (!$this->cacheDir || $this->env === 'dev') {
            return null;
        }

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir);
        }

        $config = new Configuration();

        // generate the proxies and store them as files
        $fileLocator = new FileLocator($this->cacheDir);
        $config->setGeneratorStrategy(
            new FileWriterGeneratorStrategy($fileLocator)
        );

        // set the directory to read the generated proxies from
        $config->setProxiesTargetDir($this->cacheDir);

        // then register the autoloader
        spl_autoload_register($config->getProxyAutoloader());

        return $config;
    }
}
