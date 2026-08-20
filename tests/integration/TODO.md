# План интеграционных тестов

Цель — покрыть сквозные сценарии через `Harness` + `TestApp` + реальный роутер. Юнит-уровневые проверки (Factory, Reflection, Hydrator) живут в `tests/unit/` и сюда не дублируются.

Группы сгруппированы по подсистемам `src/Lbaf/`. Внутри каждой группы — список конкретных сценариев. Чекбокс = «сделано».

---

## Routing

### FastRouteRouter
- [x] `GET /` → 200, body контроллера
- [x] `GET /unknown` → 404, `captured === null`
- [x] `POST /` на маршрут только-`GET` → 404 (зафиксировано: `METHOD_NOT_ALLOWED` уходит тем же `RouteNotFoundException`)
- [x] Маршрут с параметром: `GET /users/{id}` → параметр прокидывается в action
- [x] Маршрут с несколькими параметрами и regex-ограничением (`{id:\d+}`)
- [x] Query string: `GET /?foo=bar` → query не ломает матчинг (тест на `InjectGet` — отдельной итерацией)
- [x] Trailing slash: `/foo` vs `/foo/` — зафиксировано strict-matching
- [x] URL-encoded путь (`%20`, кириллица) — корректно декодируется в action; **отдельный вопрос:** `Json` packer без `JSON_UNESCAPED_UNICODE` — non-ASCII в выдаче `\uXXXX`
- [x] Несколько маршрутов на один handler (`#[Route]` × 2 на одном action)
- [x] `Route(['GET','POST'], ...)` — массив методов в атрибуте работает
- [x] Regex-ограничения: `[a-z]+`, slug, `\d{4}-\d{2}-\d{2}`, опциональный сегмент `[/{page:\d+}]`
- [x] Combo: один action с двумя `#[Route]`, в каждом разный набор HTTP-методов на разных путях

### Автогенерация роутов через `RouteGenerator`
- [ ] `RouteGenerator::getRoutes(null, $namespace, $folder)` собирает атрибуты из подкаталогов контроллеров
- [ ] **Открытый баг:** проверить поведение при абсолютном `controllerFolder` — в текущей реализации `$controllerFolder . str_replace($basePath, '', $item)` склеивает дубль пути. Скорее всего нужно фиксить, тест зафиксирует ожидание.
- [ ] Кэш роутов: `getRoutes($cacheFile, ...)` пишет, читает, и не перегенерирует
- [ ] Пустая папка контроллеров → `Exception('No routes found')` при включённом кэше
- [ ] Контроллер без `Route`-атрибутов в методах — игнорируется
- [ ] Метод `__construct` / `__before` / `__after` пропускаются скан­ером

### CliRouter — отложено (отдельной итерацией)

---

## Container / DI

- [ ] Автоинъекция Container в конструктор контроллера через рефлексию
- [ ] `addDefinitions([Foo::class => fn() => new Foo($cfg)])` — кастомное определение перекрывает рефлексию
- [ ] Транзитивная инъекция: контроллер зависит от сервиса, сервис — от другого сервиса
- [ ] Кэш контейнера: `get(Foo::class)` дважды → возвращается один и тот же инстанс
- [ ] `ContainerException` при запросе несуществующего класса
- [ ] `InjectRequiredArgumentException` при недостаче builtin-параметра без default

---

## Inject-атрибуты (на параметры экшена)

Покрыть каждый атрибут из `src/Lbaf/Container/Attribute/`. 

Проверить как использование непосредственно на параметре, так и на action.
Проверить как вариант когда название совпадает с именем параметра, так и переопределение. Примеры посмотри в описании класса InjectGet, в каждом тесте нужно реализовать все 4 варианта. Для варианта array обязательно проверять работу с указанием атрибута ArrayTypeOf:
- [x] `#[InjectGet('foo')]` — читает `$_GET['foo']`, все 4 варианта (method/parameter × неявный/явный ключ), типизация (int/string/array), optional с default, required → 400
- [x] `#[InjectPost('foo')]` — читает `$_POST['foo']`, все 4 варианта (method/parameter × неявный/явный ключ), типизация (int/string/array), optional с default, required → 400
- [x] `#[InjectHeader('X-Foo')]` — читает из `$_SERVER['HTTP_X_FOO']`
- [x] `#[InjectCookie('sid')]` — читает `$_COOKIE['sid']`
- [x] `#[InjectEnv('APP_ENV')]` — читает `$_ENV` / `getenv`
- [ ] `#[InjectConfig('mysql')]` — путь в конфиге (конфиг это объект) - НЕ ДЕЛАЕМ
- [x] `#[InjectValue(...)]` — литеральное значение (только TARGET_METHOD; базовый, multiple на action, типизация int/array, null + optional/required → 400)
- [x] Обязательный параметр без значения → `InjectRequiredArgumentException` → 400. Покрыто как для inject-атрибутов в каждом Inject*Test, так и для action-параметров без атрибутов в `Parameters/ParameterDefaultsTest::testRequiredParamMissingReturns400` (fallback path в `ControllerProxy::generateRunSequence`).
- [x] Опциональный параметр без значения → дефолт / null. Покрыто аналогично: `testOptionalParamMissingReturnsDefault` в каждом Inject*Test + `Parameters/ParameterDefaultsTest::testOptionalParamWithDefaultUsesDefault` и `testNullableParamGetsNull`.
- [x] `#[Inject(Foo::class)]` — все 4 варианта (method/parameter × auto-class/explicit-override через интерфейс), транзитивная инъекция (composite-сервис), вложенный `#[InjectGet]` в конструкторе сервиса читает текущий $_GET, вложенный `#[ArrayTypeOf]` хидрирует DTO-массив на уровне конструктора

---

## Controller lifecycle

- [x] `__before` выполняется до action, может пробросить аргументы или модифицировать переменные текущего контроллера (общий объект, public свойство модифицируется в __before и читается в action)
- [!] `__after` выполняется после action **только если action не отправил response** (вернул null или Response с `setStopSequenceAfterResponse(false)`). По дефолту `AbstractResponse::$stopSequenceAfterThisResponse=true`, поэтому штатный array-return action прерывает sequence — это зафиксировано в `AfterTest::testAfterIsSkippedWhenActionReturnsValue`. Сценарий «action returns null → __after runs» — `testAfterRunsWhenActionReturnsNull`.
- [x] `__before` возвращает `AbstractResponse` со `stopSequenceAfterResponse()` → action не выполняется (покрыто 3 кейса: null-return → продолжение, return массива → wraps в ApiResponse → стоп, явный AbstractResponse → стоп)
- [x] `__after` НЕ выполняется при exception в action — `AfterTest::testAfterIsSkippedWhenActionThrows`. По коду `ControllerProxy::__call` после catch бросает `ControllerExceptionWrapper` → выход из `foreach`.
- [x] Наследование контроллеров: 6 кейсов (override без `parent::`, override с `parent::`, без override → наследование) для `__before` и `__after`. См. `Lifecycle/InheritanceTest.php`. Стандартное PHP-поведение: дочерний `__before`/`__after` без `parent::` не запускает родительский; явный `parent::__before()` склеивает оба.
- [ ] `_createResponse` / `_createErrorResponse` берутся с конкретного контроллера (Api vs Web vs Cli), проверить что не ломается при переопределении

---

## ControllerProxy / обработка ошибок

- [ ] Контроллер кидает `CustomException(code=400)` → `_createErrorResponse` → 400
- [ ] Контроллер кидает `BadRequestException` → 400 (или что у CustomException::code)
- [ ] Контроллер кидает `UnauthorizedException` → 401
- [ ] Контроллер кидает обычный `\RuntimeException` → 500, `_createErrorResponse` обернул
- [ ] `ControllerExceptionWrapper` всплывает в `AbstractApplication::run` и логируется
- [ ] Ошибка при создании контроллера в Container (например `InjectRequiredArgumentException` в конструкторе) → ловится в `generateRunSequence`, отдаётся `_createErrorResponse`
- [ ] Шаблон `error_message`, `error_code`, `info_get/post/files` в `_createErrorResponse` корректен
- [ ] Throwable вне ControllerProxy (например ошибка в роутере или контейнере на уровне App) → 500 через `catch Throwable` в `run()`

---

## Response 

- [ ] `ApiResponse` + `Json` packer → правильный body, `Content-Type` (если выставляется), статус-код
- [ ] `WebResponse` → правильный Content-Type и body
- [ ] `RedirectResponse` → статус 30x, заголовок `Location`
- [ ] `CliResponse` — отдельным набором при появлении CliRouter-тестов
- [ ] Контроллер вернул скаляр/массив, не `AbstractResponse` → `_createResponse` оборачивает
- [ ] Контроллер вернул `null` → `sendResponse` не вызывается (используется в `__before`/`__after`)
- [ ] Кастомные заголовки в response: `getHeaders()` доступен в `TestApp::sendResponse`
- [ ] `stopSequenceAfterResponse()` true — последовательность прерывается, `__after` не выполняется
- [ ] `setHttpResponseCode` на `AbstractApplication` уходит в `sendHeaders` (404/500-пути)

---

## Background tasks

- [ ] `doBackground($cb)` — задача выполняется после `run()`, FIFO порядок при нескольких добавлениях
- [ ] `doBackground($cb, doInTheEnd: true)` — выполняется после обычной очереди, LIFO порядок
- [ ] Exception внутри background-задачи — поведение (текущее: проглатывается? валит остальные?). Зафиксировать.
- [ ] Background-задача добавлена из контроллера через `$container->getApplication()->doBackground(...)` — выполнится после `sendResponse`

---

## Logging

- [ ] Logger получает Info-запись об успешном выполнении action (см. `ControllerProxy::__call`)
- [ ] Logger получает Error-запись с уровнем из `CustomException::$logLevel` при падении
- [ ] `CustomException::$isError = false` → запись НЕ создаётся (это «валидная ошибка»)
- [ ] `null` logger в CLI ребрасывает throwable (текущее поведение `LogProcessor`) — зафиксировать тест с явным null logger
- [ ] Контекст лога содержит `controller`, `action`, `arguments`, `response`, `time`

---

## Config

- [ ] `Config::loadClassName(MyConfig::class)` — значения доступны через `Config::get(...)`
- [ ] `InjectConfig('key.path')` достаёт вложенные значения
- [ ] Несуществующий ключ — exception или null (зафиксировать)

---

## Application lifecycle

- [ ] `AbstractApplication::init($timeZone)` — таймзона выставляется
- [ ] Двойной `sendHeaders()` → `HeadersAlreadySendException`
- [ ] `setRouter` не вызван перед `run()` → `UnexpectedException('App router is not defined')`

---

## Приоритизация (моё предложение)

1. **Routing — параметры маршрутов и query** (близко к уже сделанному, легко).
2. **Inject-атрибуты на action** (`InjectGet`, `InjectPost`, `InjectHeader`) — основная «магия» фреймворка, должна быть зафиксирована тестами в первую очередь.
3. **ControllerProxy + exceptions** — критичный путь обработки ошибок.
4. **Controller lifecycle (`__before`/`__after`)** — поведение, которое легко сломать при рефакторинге.
5. **Response типы** (Api/Html/Redirect).
6. **Background tasks** + logging.
7. Всё остальное по мере появления потребности.
