<?php

declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core;

use EntelisTeam\Lbaf\Core\Application\RunSequence\RunSequenceItem;
use EntelisTeam\Lbaf\Core\Container\ContainerInterface;
use EntelisTeam\Lbaf\Core\Container\Exception\InjectRequiredArgumentException;
use EntelisTeam\Lbaf\Core\Container\InjectionResolver;
use EntelisTeam\Lbaf\Core\Controller\AbstractController;
use EntelisTeam\Lbaf\Core\Helper\Timer;
use EntelisTeam\Lbaf\Core\Response\AbstractResponse;
use EntelisTeam\Lbaf\Exception\BadRequestException;
use EntelisTeam\Lbaf\Exception\ControllerExceptionWrapper;
use EntelisTeam\Lbaf\Exception\LogLevelEnum;
use EntelisTeam\Lbaf\Exception\UnexpectedException;
use ReflectionClass;
use Throwable;

/**
 * обертка любых вызовов контроллера
 * @todo зачем для этого отдельный класс? Это логично убрать в prototype и все
 * @todo стоит сделать аналогичный код для модели/менеджера/библиотеки как минимум чтобы работало профилирование
 * @todo передачу аргументов в функцию контролера нужно сделать как-то более умно, возможно вынести в роутер (посмотреть другие роутеры)
 * Долго думал что это, все-таки proxy
 * A Decorator is always passed its delegatee. A Proxy might create it himself, or he might have it injected.
 * But a Proxy always knows the (more) specific type of the delegatee. In other words, the Proxy and its delegatee will have the same base type, but the Proxy points to some derived type. A Decorator points to its own base type. Thus, the difference is in compile-time information about the type of the delegatee.
 */
class ControllerProxy
{
    private string $controllerClass;
    private ContainerInterface $container;

    public function __construct(string $controllerClass, ContainerInterface $container, ?string $requiredControllerClass = null)
    {

        $this->container = $container;

        $this->controllerClass = $controllerClass;

        //@todo тут может быть некрасивый exception если нет такого класса
        //@todo дублирующийся код, вынести в функцию
        if (!is_subclass_of($controllerClass, AbstractController::class, true)) {
            throw new UnexpectedException('controller class ' . $controllerClass . ' unsupported.');
        }

        //проверяем соответствие типа
        //@todo я не уверен что это должно быть именно тут
        if (!is_null($requiredControllerClass)) {
            if (!is_subclass_of($controllerClass, $requiredControllerClass, true)) {
                throw new UnexpectedException('controller class ' . $controllerClass . ' unsupported. ' . $requiredControllerClass . ' is required.');
            }
        }

    }

    /**
     * Функция вызывается при вызове функции контроллера
     * @todo прежде чем делать логирование хорошо бы проверять что оно вообще нужно
     * @todo по сути тут нужны arguments только чтобы работали консольные вызовы. Если сделать InjectCli то можно вообще это убрать и будет единообразно
     */
    public function __call(string $action, array $arguments): void
    {
        $controllerReflection = new ReflectionClass($this->controllerClass);

        //определяем аргументы контроллера
        $methodReflection = $controllerReflection->getMethod($action);
        $methodParams = $methodReflection->getParameters();

        $methodArguments = [];

        foreach ($methodParams as $param) {
            if (isset($arguments[$param->getName()])) {
                //В php7 важен только порядок в массиве, в php8 порядок игнорируется и берутся ключи, но передавать несуществующие тоже нельзя
                $methodArguments[$param->getName()] = $arguments[$param->getName()];
            }
        }


        $sequence = $this->generateRunSequence($controllerReflection, $action, $methodArguments);

        foreach ($sequence as $item) {
            try {
                [$response, $time] = Timer::go(function () use (&$item) {
                    return call_user_func_array([$item->controller, $item->action], $item->arguments);
                });

                //@todo переписать, очень плохое временное решение
                $this->container->getApplication()->getLogger()?->log(LogLevelEnum::Info->value,
                    sprintf(
                        "Successfully executed <%s> method in <%s> controller",
                        $item->action,
                        $item->controller::class
                    ),
                    [
                        'controller' => $item->controller::class,
                        'action' => $item->action,
                        'arguments' => $item->arguments,
                        'response' => $response,
                        'time' => $time,
                    ]
                );

                //формируем отправку ответа
                //проверка на null нужна чтобы не вызвать sendResponse на __before / __after
                if (!is_null($response) && !($response instanceof AbstractResponse)) {
                    $response = $item->controller->_createResponse($response);
                }
                if (!is_null($response)) {
                    $this->container->getApplication()->sendResponse($response);
                    if ($response->stopSequenceAfterResponse()) {
                        break; //выход из foreach ($sequence as $item) {
                    }
                }

            } catch (Throwable $e) {
                //Ловим любую ошибку, возникающую в контроллере

                //@todo не самое явное поведедение - после каких-то ошибок хочется продолжать фоновые процессы, после каких-то нет...
                //@todo может быть как вариант разделить на Exception и Error, или явно ввести понятие FatalError;

                //@todo тут тоже нужен try catch
                //@todo переписать, очень плохое временное решение
                $response = $item->controller->_createErrorResponse($e);
                $this->container->getApplication()->sendResponse($response);

                //На данный момент пользователь ответ увидел если надо
                //выполнение цепочки контроллера прекращается при любой ошибке
                throw new ControllerExceptionWrapper(
                    originalException: $e, //потом будет автоматически добавлено в context
                    controller: $item->controller::class, //
                    action: $item->action,
                    context: [
                        'controller' => $item->controller::class,
                        'action' => $item->action,
                        'arguments' => $item->arguments,
                    ],
                );

            }
        }
    }

    /**
     * Функция генерирует поток выполнения приложения собирая __before, action и __after
     * @param ReflectionClass $controllerReflection
     * @param ?string $action целевое действие
     * @param array $actionArguments аргументы целевого действия
     * @param RunSequenceItem[] $sequence накопленная очередь
     * @return RunSequenceItem[]
     */
    protected function generateRunSequence(ReflectionClass $controllerReflection, ?string $action = null, array $actionArguments = [], array $sequence = []): array
    {

        //структура action -> params
        $actions = [];
        $actions['__before'] = [];
        if (!is_null($action)) {
            $actions[$action] = $actionArguments;
        }
        $actions['__after'] = [];


        //генерируем
        $sequence = [];

        /**
         * @var AbstractController $controller
         */
        try {
            $controller = $this->container->get($controllerReflection->getName());
            $controller->setContainer($this->container);
        } catch ( \Throwable $e) {
            //мы не смогли создать контроллер
            $controllerName = $controllerReflection->getName();
            return [
                new RunSequenceItem(
                    $controllerName,
                    '_createErrorResponse',
                    [$e],
                    true,
                )
            ];
        }

        foreach ($actions as $method => $methodParameters) {
            if ($controllerReflection->hasMethod($method)) {
                $methodReflection = $controllerReflection->getMethod($method);

                //строчка которая делает магию Inject в функциях контроллеров
                $skip = false;
                try {
                    $methodParameters = array_merge(
                        $methodParameters,
                        InjectionResolver::resolve($controllerReflection->getMethod($method), $this->container),
                    );
                    //проверяем что переданы все параметры
                    foreach ($methodReflection->getParameters() as $parameter) {
                        if (!isset($methodParameters[$parameter->name])) {
                            //ничего не вернулось из Inject
                            if ($parameter->isDefaultValueAvailable()) {
                                $methodParameters[$parameter->name] = $parameter->getDefaultValue();
                            } elseif ($parameter->allowsNull()) {
                                $methodParameters[$parameter->name] = null;
                            } else {
                                throw new InjectRequiredArgumentException($parameter);
                            }
                        }
                    }
                } catch (BadRequestException $e) {
                    //мы поймали ошибку плохого запроса (нет всех обязательных аргументов, etc)
                    //@todo все-таки ловить inject ошибки а не любые BadRequestException

                    $skip = true; //пропустить вставку реальной функции, вместо этого сообщение об ошибке
                    $seqItem = new RunSequenceItem(
                        $controller,
                        '_createErrorResponse',
                        [$e]
                    );
                }

                //@todo тут нужно проверять что у нас совпадает количество аргументов. Если нет - делать вывод.
                //Подумать - для консоли можно и нужно писать в консоль, для web/api как-то настраиваться должно
                //может быть какой-то errorHandle в App с ветвлением в зависимости от типа контроллера

                if (!$skip) {
                    $seqItem = new RunSequenceItem(
                        $controller,
                        $method,
                        $methodParameters
                    );
                }
                $sequence[] = $seqItem;
            }
        }

        return $sequence;
    }

}
