# Кэширующий проксификатор

При помощи [Ocramius/ProxyManager](https://github.com/Ocramius/ProxyManager) - создается кэширующее прокси над любым объектом
и (или) его публичным методом.

## Установка

`composer require `

## Использование

```php
use Prokl\CacheProxificator\CacheProxificator;
use Prokl\CacheProxificator\Resolvers\DisableVoidReturnResolver;
use Prokl\CacheProxificator\ReflectionProcessor;

class OriginalObject {
    public function getId() {
        return 1;
    }   
}

$decorator = new CacheProxificator(
    new OriginalObject(),
    new Cacher(),
    new ReflectionProcessor([
        new DisableVoidReturnResolver()
    ]),
    ['getModel']
);

$model = $decorator->getId(1);
```

После создания проксификатора к методам оригинального объекта обращаться через него. Прокси - в зависимости от  
заданной конфигурации - само решит какой метод вызывать - кэшированный или оригинальный.

Альтернативный способ: метод `proxificate(string $method, ...$params)`, обращающийся к заданному методу (кэшированному или оригинальному).

### Кэшер

Кэшером выступает любой объект, реализующий `Symfony\Contracts\Cache\CacheInterface`.

### Ресолверы

Задаются массивом через конструктор. 

Реализуют интерфейс `Prokl\CacheProxificator\Contracts\MethodResolverInterface` с одним
методом `supply(ReflectionMethod $reflectionMethod) : bool`, в котором можно решить - кэшировать ли этот метод в принципе
или нет. 

Например, можно определить - если метод ничего не возвращает, то кэширование бессмысленно. Или в названии метода
присутствует слово "cached".

Конструкторы классов всегда исключены из возможности кэширования.

В комплекте идет ресолвер `AnnotationResolver`, определяющий метод, подлежащий кэшированию с помощью аннотации 
`Cacheble`:

```php
    /**
     * @Cacheble()
     */
    public function getId(int $idElement) : int
    {

    }
```

Результат работы метода будет закэширован.

### Жесткая установка методов, подлежащих кэшированию

Задается через конструктор. Если фильтр задан, то ресолверы не исполняются.
