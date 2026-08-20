# Открытые архитектурные вопросы

- [ ] **`RouteGenerator` зависит от CWD.** Использует `realpath($controllerFolder)` от текущего рабочего каталога, ожидая что `$baseNamespace` — это родительский namespace относительно `$controllerFolder`-папки. Работает корректно, но требует chdir в правильную папку перед вызовом. В Harness обходим через `chdir`/restore. Архитектурно — стоит принимать абсолютный путь. 
- [ ] В RouteGenerator::generateRoutes логика построения classList выглядит сомнительно при абсолютном
  controllerFolder — $controllerFolder . str_replace($basePath, '', $item) склеивает дубль пути. Сейчас это  
  не мешает (мы передаём RouteItem[] явно), но при тестировании автогенерации роутов всплывёт. Стоит обсудить отдельно.
- [ ] **🐛 `Route::__construct` ломает regex.** Дефолтный `enableUrlImprovement=true` делает `str_replace('\\', '/', $this->route)` — превращает `{id:\d+}` в `{id:/d+}`. Воспроизводится: `RouteGenerator::getRoutes` на контроллере с `#[Route('GET', '/items/{id:\d+}')]` отдаёт маршрут `/items/{id:/d+}`. В тестах обходим через `enableUrlImprovement: false`. **Нужно чинить во фреймворке** — `str_replace` должен трогать только разделители пути, не содержимое regex-плейсхолдеров. Найдено интеграционным тестом `ParameterTest::testMultipleParametersWithRegexConstraint`.
- [ ] **`METHOD_NOT_ALLOWED` → 404.** Сейчас `FastRouteRouter` бросает тот же `RouteNotFoundException`. Это осознанно или баг? Решает, нужен ли отдельный 405-сценарий.
- [ ] **`Json` packer без `JSON_UNESCAPED_UNICODE`.** Non-ASCII в API-ответе экранируется как `\uXXXX`. Если нужен «человекочитаемый» UTF-8 в выдаче — править `Lbaf\Packer\Json`.
- [ ] **`final` на `sendHeaders()`.** Пока обходимся без снятия. Если потребуется проверять отправляемые заголовки в путях 404/500 (а не только status code), снимем `final` и переопределим в `TestApp`.
- [ ] **`HTTP_X_REQUEST_ID` и подобный server-state.** Если фреймворк/тестовое приложение полагается на серверные хедеры — их выставляет Harness явно. Решить какие — обязательны для всех сценариев, какие — опциональны.
- [ ] **Capture stdout.** Сейчас `TestApp::sendResponse` вообще ничего не пишет в stdout. Если появятся тесты на `echo`-side-эффекты (например, контроллеры пишут что-то в обход response) — добавить `ob_start`/`ob_get_clean` в Harness.
- [ ] **Несколько TestApp.** Когда сценарии расходятся — сделать `TestAppWithDb`, `TestAppWithCustomLogger` и т.п., или параметризовать один. Решать по факту, не заранее.
- [ ] **Container::$applicationClass** = 'App\\App' дефолтное значение помечено @todo remove — не критично, не трогал.
- [ ] **Json packer** экранирует non-ASCII как \uXXXX.
- [ ] **Асимметрия auto-resolve классов из контейнера.** В `__construct` контроллера/сервиса типизированный класс-параметр БЕЗ `#[Inject]` автоматически резолвится через `Container::get($paramType)`. В `action` / `__before` / `__after` такой же параметр без `#[Inject]` даёт `InjectRequiredArgumentException` → 400 (нет fallback'а на контейнер в `ControllerProxy::generateRunSequence`). Это удобно в одном месте и неожиданно в другом — стоит унифицировать. Зафиксировано тестами `InjectClassTest::testTypedClassInConstructorAutoResolvesThroughContainer`, `testActionWithTypedClassParamButNoInjectReturns400`, `testTypedClassInBeforeWithoutInjectReturns400`, `testTypedClassInAfterWithoutInjectReturns400`.
- [ ] **Rename Namespace & Package** - унифицировать фреймворк с остальными компонентами (Lbaf -> EntelisTeam\Lbaf)
## __before
- [ ] неявное поведение - любой return убивает обработку дальше т.к `AbstractResponse::$stopSequenceAfterThisResponse=true`

## __after
Подумать нужен ли вообще этот функционал, он как-то сильно пересекается с doBackground, + много неявного поведения (управление из ответа etc)
- [ ] `__after` выполняется после action **только если action не отправил response** (вернул null или Response с `setStopSequenceAfterResponse(false)`). По дефолту `AbstractResponse::$stopSequenceAfterThisResponse=true`, поэтому штатный array-return action прерывает sequence — это зафиксировано в `AfterTest::testAfterIsSkippedWhenActionReturnsValue`. Сценарий «action returns null → __after runs» — `testAfterRunsWhenActionReturnsNull`.
- [ ] `__after` **НЕ выполняется** при exception в action

# Идеи по улучшению

## Daemon
- [ ] Добавить обработку сигналов
- [ ] Добавить возможность запуска форков/фоновых скриптов (в идеале с встроеным транспортом данных типа корутин)
