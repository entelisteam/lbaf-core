# entelisteam/lbaf
Простой и быстрый PHP framework.

## Install

```bash
composer require entelisteam/lbaf
composer require --dev entelisteam/lbaf-rector
```

Для обновления кода до актуальной версии обратитесь к [документации по rector](https://github.com/entelisteam/lbaf-rector)

## Development
```bash
COMPOSER=dev-composer.json composer update
```

## Использование

### Точка входа и инициализация

Своё приложение наследуем от `AbstractApplication` (обычно пустой класс, вся логика во фреймворке):

```php
// src/App.php
namespace App;

use EntelisTeam\Lbaf\Core\Application\AbstractApplication;

class App extends AbstractApplication {}
```

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

use App\App;use EntelisTeam\Lbaf\Core\Router\FastRouteRouter;use EntelisTeam\Lbaf\Core\Router\Route\RouteGenerator;

$app = App::init('Europe/Moscow');          // init(?string $timeZone) — таймзона опциональна
$app->setLogger($psrLogger);                // опционально, любой PSR-3 LoggerInterface
$app->setRouter(new FastRouteRouter(
    RouteGenerator::getRoutes(null, 'App', 'Controller')
));
$app->run();
```

`setRouter()` обязателен — без него `run()` кинет `UnexpectedException`. `App::init()` сам поднимает `Container` и возвращает инстанс приложения.

`RouteGenerator::getRoutes($cacheFile, $baseNamespace, $controllerFolder)` сканирует контроллеры по атрибутам `#[Route]`:
- контроллеры лежат в `<cwd>/<controllerFolder>/…`, неймспейс — `<baseNamespace>\<controllerFolder>\…` (т.е. `App\Controller\UserController` → `Controller/UserController.php`);
- **важно:** скан идёт относительно текущей рабочей директории, поэтому перед `run()` cwd должен указывать на каталог с папкой `Controller/` (пример обхода — `tests/integration/Harness::routes()`, делает `chdir`);
- на проде передай путь в `$cacheFile` — роуты сериализуются в файл и не пересканируются (`getRoutes(__DIR__.'/cache/routes.php', …)`).

Кастомные определения в контейнер (если рефлексии мало) — до `run()`:

```php
$app->getContainer()->addDefinitions([
    PDO::class => fn() => new PDO($dsn, $user, $pass),
]);
```

### Контроллеры и роутинг

Контроллер наследуется от одного из базовых классов — он определяет формат ответа:

| Базовый класс | Ответ | `_createResponse` |
|---------------|-------|-------------------|
| `AbstractApiController` | JSON | `ApiResponse` + `Json` packer |
| `AbstractWebController` | HTML | `WebResponse` |
| `AbstractCliController` | консоль | `CliResponse` |

Метод помечается атрибутом `#[Route]`, возвращаемое значение (массив/скаляр) автоматически оборачивается в нужный Response:

```php
namespace App\Controller;

use EntelisTeam\Lbaf\Core\Controller\AbstractApiController;use EntelisTeam\Lbaf\Core\Router\Attribute\Route;

class UserController extends AbstractApiController
{
    #[Route('GET', '/users/{id}')]            // параметр пути → аргумент метода
    public function show(string $id)
    {
        return ['id' => $id];                 // → {"id":"..."} со статусом 200
    }

    #[Route('GET', '/users/{id:\d+}', enableUrlImprovement: false)]
    public function showNumeric(string $id) { /* ... */ }

    #[Route(['GET', 'POST'], '/users')]       // массив методов
    #[Route('GET', '/people')]                // #[Route] повторяемый — несколько путей на один экшен
    public function index() { /* ... */ }
}
```

Параметры пути FastRoute: `{name}`, regex `{id:\d+}`, опциональный сегмент `[/{page:\d+}]`. Для regex с обратным слешем (`\d`) ставь `enableUrlImprovement: false`, иначе слеши «починятся» и паттерн сломается.

### Внедрение данных запроса (Inject)

Источники данных подставляются в аргументы экшена через атрибуты. Тип аргумента приводится автоматически (`int`, `array`); optional/nullable без значения → default/null; обязательный без значения → ответ **400**.

| Атрибут | Источник |
|---------|----------|
| `#[InjectGet]` | `$_GET` |
| `#[InjectPost]` | `$_POST` |
| `#[InjectHeader]` | `$_SERVER['HTTP_*']` (имя нечувствительно к регистру и дефисам) |
| `#[InjectCookie]` | `$_COOKIE` |
| `#[InjectEnv]` | `$_ENV` / `getenv()` |
| `#[InjectValue]` | литеральное значение (только на методе) |

Четыре формы записи (на примере `InjectGet`, ключ `$_GET['foo']`):

```php
#[InjectGet('foo')]                                   // 1. на методе, имя = имя параметра
public function a(string $foo) {}

public function b(#[InjectGet] string $foo) {}        // 2. на параметре, ключ = имя параметра

#[InjectGet('foo', 'customKey')]                      // 3. на методе, $_GET['customKey'] → $foo
public function c(string $foo) {}

public function d(#[InjectGet('customKey')] string $foo) {}  // 4. на параметре, явный ключ
```

Типизация и массивы (`ArrayTypeOf` гидрирует элементы массива в нужный тип/DTO):

```php
public function typed(#[InjectGet] int $count) {}                         // "5" → 5
public function ints(#[InjectGet] #[ArrayTypeOf('int')] array $nums) {}   // ["1","2"] → [1,2]
public function dtos(#[InjectGet] #[ArrayTypeOf(ItemDto::class)] array $items) {}  // → ItemDto[]

#[InjectValue('count', 42)]                                               // литерал
public function fixed(int $count) {}
```

### Внедрение сервисов (DI)

Аргумент с типом-классом и `#[Inject]` создаётся контейнером (рефлексия конструктора, кеш-синглтон на запрос). Зависимости резолвятся транзитивно, в конструкторе сервиса работают те же `#[Inject*]`:

```php
public function action(#[Inject] UserRepository $repo) {}                 // авто по типу

public function byIface(#[Inject(MysqlUserRepo::class)] UserRepository $repo) {} // конкретный класс под интерфейс

#[Inject('repo', MysqlUserRepo::class)]                                   // форма на методе
public function alt(UserRepository $repo) {}
```

`#[Inject]` работает и в конструкторе самого контроллера:

```php
class UserController extends AbstractApiController
{
    public function __construct(#[Inject] public UserRepository $repo) {}
}
```

### Жизненный цикл: `__before` / `__after`

Если контроллер объявляет `__before`/`__after`, они выполняются вокруг экшена (в них тоже доступны `#[Inject*]`). Контроллер — один объект, public-свойства разделяются между методами:

```php
class SecureController extends AbstractApiController
{
    public function __before(#[InjectHeader('authorization')] ?string $auth = null)
    {
        if ($auth !== 'secret') {
            return ['error' => 'forbidden'];   // вернул не-null → экшен НЕ выполнится (short-circuit)
        }
        return null;                            // вернул null → выполнение продолжается
    }

    #[Route('GET', '/secure')]
    public function index() { return ['ok' => true]; }

    public function __after() { /* выполнится, только если экшен вернул null (не отправил ответ) */ }
}
```

Ключевой момент: любой отправленный ответ останавливает цепочку (`AbstractResponse::$stopSequenceAfterThisResponse = true` по умолчанию). Поэтому обычный экшен, вернувший массив, до `__after` не доходит — `__after` нужен для пост-обработки экшенов, возвращающих `null`. Наследование — стандартное PHP: дочерний `__before` без `parent::__before()` родительский не вызовет.

### Ответы и заголовки

Можно вернуть массив/скаляр (обернётся автоматически) либо собрать `AbstractResponse` вручную:

```php
use EntelisTeam\Lbaf\Core\Response\Header;use EntelisTeam\Lbaf\Core\Response\RedirectResponse;

#[Route('GET', '/old')]
public function redirect()
{
    return new RedirectResponse('/new', 301);   // Location + статус
}

#[Route('GET', '/custom')]
public function custom()
{
    return self::_createResponse(['ok' => true])
        ->setHttpResponseCode(201)
        ->setHeader(new Header('X-App', 'lbaf'));
}
```

### Обработка ошибок

Брошенное исключение ловится в `ControllerProxy` и проходит через `_createErrorResponse` своего контроллера. У наследников `CustomException` (из `entelisteam/lbaf-exception`) `code` трактуется как HTTP-статус:

```php
use EntelisTeam\Lbaf\Exception\BadRequestException;   // 400
use EntelisTeam\Lbaf\Exception\UnauthorizedException; // 401

throw new BadRequestException('id обязателен');        // → 400, тело с error_message/error_code
// любой другой Throwable → 500
```

### Фоновые задачи

Выполняются после `run()` (после `fastcgi_finish_request`, т.е. уже после ответа клиенту):

```php
$this->getContainer()->getApplication()->doBackground(fn() => $this->sendEmail());
// $doInTheEnd: true — выполнить в самом конце, в порядке LIFO
```

### CLI

Для консоли — `CliRouter` и контроллеры от `AbstractCliController`:

```php
// bin/console.php
$app = App::init();
$app->setRouter(new \EntelisTeam\Lbaf\Core\Router\CliRouter($argv, 'App', 'Controller'));
$app->run();
```

Вызов: `php bin/console.php Cli/ImportController run --file=data.csv` → контроллер `App\Controller\Cli\ImportController`, метод `run`, `--file` приедет в одноимённый аргумент `string $file`.

## Пакеты и зависимости

| Пакет | Зависимости | Описание |
|-------|-------------|----------|
| `entelisteam/lbaf-hydrator` | `php-reflection-helpers` | Гидратор DTO |
| `entelisteam/lbaf-mysql` | `lbaf-exception` | Обёртка над mysqli |
| `entelisteam/lbaf-exception` | — | Исключения фреймворка |
| `entelisteam/php-reflection-helpers` | — | Утилиты для рефлексии |
| `entelisteam/lbaf-rector` *(dev)* | — | Rector для миграций |

## Версионирование

Все пакеты LBAF следуют [SemVer](https://semver.org):

- **Major (`1.x` → `2.0`)** — слом обратной совместимости публичного API. Каждое такое изменение сопровождается Rector-миграцией (см. [lbaf-rector](https://github.com/entelisteam/lbaf-rector)). Обновляется только вручную: поднять constraint в `composer.json` и выполнить `composer update`.
- **Minor (`1.2` → `1.3`)** — новая функциональность, обратная совместимость сохранена.
- **Patch (`1.2.0` → `1.2.1`)** — исправления без изменения публичного API.

Правило: **если изменение требует Rector-миграции — это major**, иначе minor или patch.

Зависимости на пакеты LBAF указываются через caret (`"entelisteam/lbaf-*": "^1.2"`): minor и patch подтягиваются обычным `composer update`, major автоматически не устанавливается. После обновления Rector-миграции применяются автоматически (хук `post-update-cmd`); если хук не настроен — выполните `composer rector:fix`.

## История изменений

### 2026-05-11
- BREAKING CHANGE: Разделение кода на пакеты, используйте Rector для обновления кода.

### 2026-01-06 
- BREAKING CHANGE: Removed \Lbaf\Init::init();
- NEW FEATURE: Inject & Inject* CAN target method arguments

### 2024-03-20 
- BREAKING CHANGE: Controller::_createResponse и _createErrorResponse теперь статические, нужно обновить все конструкторы проекта
- BREAKING CHANGE: AbstractController больше не имеет конструктора, вызов parent::__construct из контроллера вызовет ошибку

### 2023-10-16 
- BREAKING CHANGE: функции контроллера/приложения success/error/__missedRequiredArgument/sendHeaders больше не доступны
- BREAKING CHANGE: функция redirect тоже недоступна - используйте RedirectResponse
- BREAKING CHANGE: возврат из __before вызывает остановку исполнения приложения. Если приложеение должно выполняться дальше - return void.
- Формат ответа теперь настраивается через служебные функции контроллера _createResponse / _createErrorResponse
- KNOWN ISSUES: ошибка из CLI контроллера отображается дважды
- todo: научиться возвращать template(?)
- todo: переосмыслить LogProcessor::process - не должно быть разницы есть логгер или нет, поэтому он больше не кидает ошибку дальше
- todo: придумать способ подавлять вывод некоторых ошибок наружу - сейчас это идет через контроллер createErrorResponse что может быть не красиво на бою (ну или делать это в контроллерах каждый раз)


### 2023-06-19 
- BREAKING CHANGE: Inject Attribute больше не могут быть указаны как свойства класса
- Добавлен атрибут ArrayTypeOf для типизации массивов при создании через Factory или через Inject

### 2023-04-12 
- BREAKING CHANGE: убрана магия с вызовом __before / __after по цепочке родителей контроллера. Если где-то это ожидалось - нужно явно прописать вызов parent::__before

### 2023-04-07 
- BREAKING CHANGE: изменена сигнатура функции Lbaf\Helper\curl::post - в середину добавлен аргумент $getParameters

### 2023-03-22 
- NEW FEATURE: Добавлен метод App::setHttpResponseCode
- FIXED: Заголовки определенные в App отправляются всегда


### 2023-03-15 
- BREAKING CHANGE: Изменена сигнатура функции error контроллеров
- BREAKING CHANGE: Изменена сигнатура функции Lbaf\Helper\curl::get
- BREAKING CHANGE: Базовый класс ошибки ErrorException переименован в CustomException
- Добавлен функционал PSR совместимого логгера $app->setLogger(Psr\Log\LoggerInterface $logger)