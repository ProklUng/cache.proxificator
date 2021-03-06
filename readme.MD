# Кэширующий проксификатор

При помощи [FriendsOfPHP/proxy-manager-lts](https://github.com/FriendsOfPHP/proxy-manager-lts) - создается кэширующее прокси над любым объектом
и (или) его публичным методом.

## Установка

`composer require proklung/cache-proxificator`

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
    ['getModel'],
    'prod' 
);

$model = $decorator->getId();
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
use Prokl\CacheProxificator\CacheProxificator;
use Prokl\CacheProxificator\Resolvers\AnnotationResolver;
use Prokl\CacheProxificator\ReflectionProcessor;
use Prokl\CacheProxificator\Resolvers\Annotations\Cacheble;

class OriginalObject {
   /**
     * @Cacheble()
     */
    public function getId() {
        return 1;
    }   
}

$decorator = new CacheProxificator(
    new OriginalObject(),
    new Cacher(),
    new ReflectionProcessor([
        new AnnotationResolver(
            container()->get('annotations.reader')
        )
    ]),
    [],
    'prod'    
);

$model = $decorator->getId();
```

Последний, опциональный, параметр конструктора - `cachePath` - путь к месту, где хранятся на проде сгенерированные прокси-файлы.
По умолчанию - в папке vendor пакета, что исключает инвалидацию в случае изменения оригинальных файлов.

Результат работы метода будет закэширован.

### Жесткая установка методов, подлежащих кэшированию

Задается через конструктор. Если фильтр задан, то ресолверы не исполняются.

### Прегенерация прокси-классов на продакшене

По [мотивам](https://github.com/Ocramius/ProxyManager/blob/2.12.x/docs/tuning-for-production.md).

Если в конструкторе задать параметр `$environment`, отличный от `dev` (по умолчанию), то прокси-классы будут прегенерироваться
в отдельные файлы. Путь к кэшу задается защищенным свойством `$cacheDir`, которое подлежит изменению через наследование.

Также, если это свойство пустое, то прегенерация не будет работать вне зависимости от значения окружения.
 
## Абстрактнее

**INTERNAL**

Более абстрактный подход, позволяющий загнать не только кэшер.

Класс `Prokl\CacheProxificator\ProxificatorAbstraction`, где конкретные обработчики [pre-access interceptor](https://github.com/Ocramius/ProxyManager/blob/2.12.x/docs/access-interceptor-value-holder.md) и
[post-access interceptor](https://github.com/Ocramius/ProxyManager/blob/2.12.x/docs/access-interceptor-value-holder.md)
вынесены в отдельные классы, реализующие интерфейсы `Prokl\CacheProxificator\Contracts\OcramiusProxyHandlerPreInterface` и `Prokl\CacheProxificator\Contracts\OcramiusProxyHandlerPostInterface` соответственно.

Один метод - `public function handler($proxy, $instance, $method, $params, $returnValue, &$returnEarly)`, параметры согласно документации.

Кэшер вынесен в такой класс - `Prokl\CacheProxificator\Handlers\CacheHandler`.

Инициализация:

```php
use Prokl\CacheProxificator\Handlers\CacheHandler;
use Prokl\CacheProxificator\ProxificatorAbstraction;
use Prokl\CacheProxificator\ReflectionProcessor;

class OriginalObject {
    public function getId() {
        return 1;
    }   
}

// Вынесенный в отдельный класс обработчик кэша.
$cacheHandler = new CacheHandler(
    container()->get('example.cacher'),
    new ReflectionProcessor()
);

$decorator = new ProxificatorAbstraction(
    new OriginalObject,
    new ReflectionProcessor(),
    $cacheHandler,
    null,
    ['getId'],
    'dev'    
);

$model = $decorator->getId();
```
