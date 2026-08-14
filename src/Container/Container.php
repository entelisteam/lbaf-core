<?php
declare(strict_types=1);

namespace EntelisTeam\Lbaf\Core\Container;

use EntelisTeam\Lbaf\Core\Application\AbstractApplication;
use EntelisTeam\Lbaf\Core\Container\Exception\ContainerException;
use EntelisTeam\Lbaf\Core\Container\Exception\InjectRequiredArgumentException;
use ReflectionClass;
use TypeError;

/**
 * @todo возможно стоит добавить implements DI\FactoryInterface, Invoker\InvokerInterface (хз)
 * @todo взрослые ребята задают настройки через DI\ContainerBuilder но мне было лень
 * @todo может быть добавить синглтон? и прописать самого себя как getInstance) ))
 */
class Container implements ContainerInterface
{
    protected array $definitions = [];
    protected array $cache = [];

    /**
     * @todo remove default value
     */
    protected string $applicationClass = 'App\\App';

    public function __construct()
    {
        $this->addDefinitions([
            self::class => function () {
                return $this;
            },
        ]);
    }

    /**
     * @param array $definitions
     * @return void
     * @todo подумать, m.b сделать $definitions тоже классом
     */
    public function addDefinitions(array $definitions): self
    {
        foreach ($definitions as $key => $item) {
            $this->definitions[$key] = $item;
        }
        return $this;
    }

    /**
     * @todo добавить проверку implements abstract application
     */
    public function setApplicationClass(string $applicationClass): self
    {
        $this->applicationClass = $applicationClass;
        return $this;
    }

    public function getApplicationClass(): string
    {
        return $this->applicationClass;
    }

    public function getApplication(): AbstractApplication
    {
        return $this->get($this->applicationClass);
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     * @template T
     * @param T $id Identifier of the entry to look for.
     * @return T instance.
     * @todo кидать exception правильного типа
     */
    public function &get(string $id): mixed
    {

        if (!isset($this->cache[$id])) {
            //пробуем найти сопоставления
            if (isset($this->definitions[$id])) {
                $closure = $this->definitions[$id];
                $this->cache[$id] = $closure();
            } else {
                if (!class_exists($id)) {
                    throw new ContainerException('Container fail: class ' . $id . ' not found');
                }

                //сопоставлений нет и нужно честно искать
                $classReflection = new ReflectionClass($id);
                $args = [];
                $constructor = $classReflection->getConstructor();

                if ($constructor) {
                    //получаем параметры через явный инжект.
                    $args = InjectionResolver::resolve($constructor, $this);

                    //Всё остальное
                    $params = $constructor->getParameters();
                    foreach ($params as $param) {
                        /**
                         * У нас есть массив параметров конструктора.
                         * В нем есть обязательные и не обязательные переменные.
                         * Нам нужно посмотреть что вернулось после inject:
                         *  - если не вернулось ничего и тип параметра класс - попытаться догрузить через Container get
                         *  - если вернулся null и параметр не nullable - кинуть exception
                         */
                        $paramName = $param->getName();
                        if (!array_key_exists($paramName, $args)) {
                            //inject не было вообще. Если это не опциональный параметр - нужно попытаться его загрузить
                            if ($param->isDefaultValueAvailable()) {
                                $args[$paramName] = $param->getDefaultValue();
                            } elseif ($param->allowsNull()) {
                                $args[$paramName] = null;
                            } else {
                                //кусок отвечающий за автолоад классов в конструкторах
                                //возможно стоит отключить для единообразия
                                if ($param->getType()->isBuiltin()) {
                                   $e = new InjectRequiredArgumentException($param);
                                   $e->isError = true; //это ошибка потому что пропущен inject вообще
                                   throw $e;
                                } else {
                                    //@todo try catch, если упало - то тоже кидать ошибку Inject
                                    $args[$paramName] = $this->get($param->getType()->getName());
                                }
                            }
                        } elseif (is_null($args[$paramName])) {
                            //из Inject вернулся null.

                            //пытаемся взять дефолтное значение
                            //не пытаемся тут загрузить через контейнер, тк у нас был явно указан Inject
                            if ($param->isDefaultValueAvailable()) {
                                $args[$paramName] = $param->getDefaultValue();
                            } elseif ($param->allowsNull()) {
                                $args[$paramName] = null;
                            } else {
                                throw new InjectRequiredArgumentException($param);
                            }
                        }

                    }
                }


                //@todo я не придумал зачем и как нам это ловить
                //проблема в том, что newInstanceArgs игнорирует strict_types
                try {
                    $classInstance = $classReflection->newInstanceArgs($args);
                } catch (TypeError $e) {
                    throw $e;
                }

                $this->cache[$id] = &$classInstance;
            }
        }

        return $this->cache[$id];
    }

}
