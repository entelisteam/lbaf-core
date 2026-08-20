<?php

namespace EntelisTeam\Lbaf\Core\Application;

use Closure;
use EntelisTeam\Lbaf\Core\Container\Container;
use EntelisTeam\Lbaf\Core\Container\ContainerTrait;
use EntelisTeam\Lbaf\Core\Helper\Console;
use EntelisTeam\Lbaf\Core\Helper\Timer;
use EntelisTeam\Lbaf\Core\Logger\LogProcessor;
use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Core\Response\Header;
use EntelisTeam\Lbaf\Core\Response\HeadersAlreadySendException;
use EntelisTeam\Lbaf\Core\Router\RouteNotFoundException;
use EntelisTeam\Lbaf\Core\Router\RouterInterface;
use EntelisTeam\Lbaf\Exception\ControllerExceptionWrapper;
use EntelisTeam\Lbaf\Exception\CustomException;
use EntelisTeam\Lbaf\Exception\UnexpectedException;
use Psr\Log\LoggerInterface;
use Throwable;
use function fastcgi_finish_request;

abstract class AbstractApplication
{
    use ContainerTrait;

    /**
     * @var Header[]
     */
    protected array $headers = [];
    /**
     * @var bool были ли отправлены заголовки
     */
    protected bool $headers_sent = false;
    private RouterInterface $router;
    /**
     * @var Closure[] массив задач для выполнения после закрытия канала
     */
    private array $backgroundTaskList = [];
    /**
     * @var Closure[] массив задач для выполнения после закрытия канала
     */
    private array $backgroundTaskListAfterAll = [];

    protected ?LoggerInterface $logger = null;

    protected int $httpResponseCode = 200;

    /**
     * Общий универсальный конструктор объекта application
     * Подгрузка конфигов, определение переменных
     */
    public function __construct(
        Container $container
    )
    {
        $this->setContainer($container);
    }

    public final function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public final function &getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public final function setHttpResponseCode(int $httpResponseCode): self
    {
        $this->httpResponseCode = $httpResponseCode;
        return $this;
    }

    public final function run(): void
    {
        //помним что мы скорее всего внутри объекта уровня приложения

        if (!($this->router instanceof RouterInterface)) {
            //@todo типизировать
            throw new UnexpectedException('App router is not defined');
        }

        try {
            /**
             * @todo подумать как отрефакторить
             * 1. router->dispatch может или кинуть RouteNotFoundException который как-то кривенько но обрабатывается,
             * 2. или кинуть просто ошибку которая не обрабатывается
             * 3. или создать ControllerProxy и сделать его вызов откуда тоже могут приехать ошибки
             **/
            $this->router->dispatch($this->container);
        } catch (RouteNotFoundException $e) {
            //controllerProxy не стартовал, нужно выполнить фоновые задачи и умереть
            //@todo сделать возможность настраивать поведение.
            //отправляем http_response_code т.к контроллер не запускался

            if (!$this->headers_sent) {
                $this->setHttpResponseCode(404);
                $this->sendHeaders();
            }

            LogProcessor::process($this->logger, $e);

        } catch (ControllerExceptionWrapper $wrappedException) {
            if (!$this->headers_sent) {
                //заглушка на всякий случай, ControllerProxy уже вызвал Controller->error
                $this->setHttpResponseCode(($wrappedException->originalException instanceof CustomException) ? $wrappedException->originalException->getCode() : 500);
                $this->sendHeaders();
            }

            $originalException = $wrappedException->originalException;

            LogProcessor::process(
                logger: $this->logger,
                throwable: $originalException,
                context: $wrappedException->context,
                message: sprintf(
                    "Exception <%s> message <%s> caught when executing <%s> method in <%s> controller",
                    $originalException::class,
                    $originalException->getMessage(),
                    $wrappedException->action,
                    $wrappedException->controller,
                )
            );

        } catch (Throwable $e) {
            //что-то пошло сильно не так т.к ошибку не поймали в ControllerProxy
            //например эта ошибка в контейнере при создании контроллера
            //@todo подумать как быть - например иметь дефолтный обработчик таких ошибок

            if ($e instanceof CustomException) {
                $e->isError = true;
            }

            $this->setHttpResponseCode(500);
            if (!$this->headers_sent) {
                $this->sendHeaders();
            }

            LogProcessor::process($this->logger, $e);

        }
        //общая обработка быстродействия

        //@todo тут море доделок.
        //@todo подумать, может быть перехватывать вывод целиком

        //@todo сюда можно добавить вывод профайлера

        //выполняем фоновые задачи
        $this->shutdownFunction();
    }

    function shutdownFunction()
    {
        $this->processBackgroundTasks();

        //@todo подумать куда это пихнуть - profiler это классная штука которую хочется использовать для общей отладки, в том числе всяких shutdown
        //@todo нужны какие-то настройки отдельные в lbaf куда отправлять все что насобирал profiler, ведь он собирает и то что в __after
        //profiler::print();
    }

    /**
     * @todo убрать дублирование кода
     * @todo добавить try/catch
     * @todo подумать protected final или private
     */
    protected final function processBackgroundTasks(): void
    {
        if (function_exists('\fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        foreach ($this->backgroundTaskList as $key => $func) {
            $func();
            unset($this->backgroundTaskList[$key]); //защита от повторных вызовов
        }

        $this->backgroundTaskListAfterAll = array_reverse($this->backgroundTaskListAfterAll);

        //@todo убрать дублирование кода
        foreach ($this->backgroundTaskListAfterAll as $key => $func) {
            $func();
            unset($this->backgroundTaskListAfterAll[$key]); //защита от повторных вызовов
        }
    }
    //@todo подумать нужно ли этой функции быть внутри app?
    //@todo есть какашка 💩: shutdownFunction запускается через register_shutdown_function и там уже не работает автолоадер

    /**
     * Добавляет задачи в очередь на обработку
     * @param Closure $callback
     * @param bool $doInTheEnd false (default) - задачи будут выполнены FIFO. true - задачи будут выполнены в конце LIFO
     */
    public final function doBackground(Closure $callback, bool $doInTheEnd = false): void
    {
        if ($doInTheEnd) {
            $this->backgroundTaskListAfterAll[] = $callback;
        } else {
            $this->backgroundTaskList[] = $callback;
        }
    }

    public final function setRouter(RouterInterface $router): self
    {
        $this->router = $router;
        return $this;
    }

    final function __destruct()
    {
        /**
         * @todo тут нужно написать какие-то проверки
         * 1. что выполнены все фоновые задачи
         * 2. что все события ушли в транспорт
         * учесть что в этот момент все либы итд могут быть уже отвалившимися
         */
        #print "Уничтожается " . __CLASS__  . "<br>";
    }

    //@todo remove
    protected final function sendHeaders(): self
    {
        if ($this->isHeadersSend()) {
            throw new HeadersAlreadySendException();
        }
        $this->headers_sent = true;

        http_response_code($this->httpResponseCode);
        foreach ($this->headers as $header) {
            $header->send();
        }

        return $this;
    }


    /**
     * Отправляет ответ в вывод
     * @param AbstractResponse $response
     * @return void
     * @throws HeadersAlreadySendException
     */
    public function sendResponse(AbstractResponse $response): void
    {
        if (!Console::isConsole()) {
            if ($this->isHeadersSend()) {
                //@todo подумать должно ли тут это быть
                throw new HeadersAlreadySendException();
            }
            $this->headers_sent = true;

            $httpResponseCode = $response->getHttpResponseCode();
            if (!is_null($httpResponseCode)) {
                http_response_code($httpResponseCode);
            }

            foreach ($response->getHeaders() as $header) {
                $header->send();
            }
        }

        echo $response->pack();

    }

    /**
     * Были ли отправлены заголовки
     */
    protected final function isHeadersSend(): bool
    {
        return $this->headers_sent;
    }

    /**
     * Создает объект приложения
     * @param ?string $timeZone
     * @return static
     * @todo подумать над удалением Timer из кода проекта
     */
    static function init(?string $timeZone = null): static
    {
        if (!is_null($timeZone)) {
            date_default_timezone_set($timeZone);
        }

        Timer::click('init');

        $container = new Container();
        $container->setApplicationClass(static::class);
        return $container->getApplication();

    }



}
