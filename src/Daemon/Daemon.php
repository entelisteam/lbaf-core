<?php

namespace EntelisTeam\Lbaf\Core\Daemon;

use Closure;
use EntelisTeam\Lbaf\Core\Database\Rabbit;
use EntelisTeam\Lbaf\Core\Database\RabbitWorkerOptions;
use EntelisTeam\Lbaf\Core\Database\Redis;
use EntelisTeam\Lbaf\Core\Logger\LogProcessor;
use EntelisTeam\Lbaf\MySql\MySql;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * @todo [low] подумать, может быть это не отдельный класс, а еще один тип контроллера?
 * @todo [low] Сделать его абстрактным, вместо callback функции использовать функцию класса-наследника, а все что в _process делать через __call обертку
 */
class Daemon
{

    private ?MySql $db = null;
    private ?Redis $redis = null;

    private ?string $exchange = null;
    private ?array $topics = null;

    function __construct(
        private Rabbit        $rabbit,
        private DaemonOptions $options
    )
    {
    }

    public function useDb(MySql &$db)
    {
        $this->db = $db;
    }

    public function useRedis(Redis &$redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param string $exchange
     * @param array $topics
     * @param string $queueName
     * @param callable $userFunc
     * @return void
     * @throws Throwable
     * @todo определиться с порядком required/optional
     */
    public function processExchange(string $exchange, Closure $userFunc, array $topics = ['#'], string $queueName = ''): void
    {
        $this->exchange = $exchange;
        $this->topics = $topics;
        $this->_process($queueName, $userFunc);
    }

    /**
     * @throws Throwable
     */
    private function _process(string $queueName, Closure $callback): void
    {
        $rabbit = $this->rabbit;
        $exception_mode = $this->options->exceptionMode;
        $fail_mode = $this->options->failMode;

        // Обертка над переданным нам $callback
        $callbackWrapper = function (AMQPMessage $msg) use ($queueName, &$callback, $rabbit, $exception_mode, $fail_mode) {
            try {
                // Восстанавливаем соединение с базой, если необходимо
                // если что-то пойдет не так, демон умрет и будет перезапущен извне
                // @todo переделать - должно быть как-то расширяемо
                if (!is_null($this->db)) {
                    $this->db->ping();
                }
                if (!is_null($this->redis)) {
                    $this->redis->ping();
                }

            } catch (Throwable $e) {
                //@todo разобраться с App

                //$app->log('[FAILURE] ' . $exception->getMessage() . ' file ' . $exception->getFile() . ' line ' . $exception->getLine(), 'FAILURE');
                //$app->log('Trace: ' . $exception->getTraceAsString(), 'FAILURE');
                throw $e;
            }

            try {
                //@todo может быть обертку над этим
                $json = json_decode($msg->body);

                //@todo сейчас код не полноценен в плане exchange
                //$routing_key = $msg->delivery_info['routing_key'];
                //$result = $callback($json, $routing_key);

                //@todo добавить Timer::go как в ControllerProxy
                //@todo добавить логирование успешной обработки события (не уверен что стоит, может очень замедлять все)

                $result = $callback($json);


                // Обработка отрицательного результата
                if (!$result) {
                    $rabbit->finalizeMessage($msg, $fail_mode, $queueName);
                } else {
                    $rabbit->confirm($msg);
                }
            } catch (Throwable $throwable) {
                //@todo не уверен что это надо. может быть завязаться как-то на CliController->error ?
                error_log('Exception occurred when processed message: ' . $msg->getBody());
                error_log('    exception details: ' . $throwable->getMessage());


                LogProcessor::process($this->options->logger, $throwable);

                // Обработка возникших исключений
                $rabbit->finalizeMessage($msg, $exception_mode, $queueName);
            }
        };

        // Запускаем воркера, используя обертку
        $this->rabbit->startWorker(
            $queueName,
            $callbackWrapper,
            new RabbitWorkerOptions(
                qos: $this->options->qos,
                exchange: $this->exchange,
                topics: $this->topics,
            )
        );
    }

    /**
     * Обрабатывает сообщения из очереди с помощью переданной функции
     * Если очередь не существует, она будет создана
     * @param string $queueName Название очереди
     * @param Closure $callback Функция func($json):bool
     * @throws Throwable
     * @todo внедрить обработку exception уровня бизнес-логики
     */
    public function processQueue(string $queueName, Closure $callback): void
    {
        $this->exchange = null;
        $this->topics = null;

        $this->_process($queueName, $callback);
    }

    public function ping(){
        $this->rabbit->ping();
    }
}