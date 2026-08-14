<?php

namespace EntelisTeam\Lbaf\Core\Database;

use Closure;
use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

/**
 * RabbitMQ
 * @uses php-amqplib/php-amqplib
 * @uses php extensions: sockets pcntl zip
 * @uses apt: zlib1g-dev libzip-dev
 * @todo test
 * @todo refactor
 * @todo все константы, скорее всего, нужно вынести в конфиг
 */
class Rabbit
{
    /**
     * Количество попыток подсоединиться
     */
    private const CONNECT_TRY_COUNT = 5;

    /**
     * Количество попыток отправить данные
     */
    private const PUSH_TRY_COUNT = 10;

    /**
     * Таймаут ожидания чтения
     */
    private const CONSUMER_WAIT_TIMEOUT = 300;
    private const RPC_WAIT_TIMEOUT = 300;
    private const HEARTBEAT = 60;
    private const TMP_QUEUE_TTL = 900000; // 15 минут

    /**
     * Кол-во попыток создания временной очереди
     */
    private const TMP_QUEUE_RECREATE_TRY_COUNT = 100;

    private ?AMQPStreamConnection $connection;
    private ?AMQPChannel $channel;

    private RabbitConfig $config;

    function __construct(RabbitConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Создать/получить очередь с определенными свойствами
     *
     * @param string $queueName
     * @param array $options [passive, durable, exclusive, auto_delete]
     * @return false|string|null
     *
     * @todo наверное, нужно вынести в класс $options как RabbitWorkerOptions (RabbitQueueOptions)
     */
    public function createQueue(string $queueName, array $options = [])
    {
        return $this->_declareQueue($queueName, $options);
    }

    /**
     * Создание временной очереди
     *
     * @param array $options
     * @return string
     */
    public function createTmpQueue(array $options = []): string
    {
        $tmpQueue = '';
        for ($i = 0; $i < self::TMP_QUEUE_RECREATE_TRY_COUNT; $i++) {
            try {
                $tmpQueue = $this->_declareQueue('', $options);
                if (!empty($tmpQueue)) {
                    break;
                }
            } catch (Exception $e) {
                error_log(date('[d-m-Y H:i:s e]') . ' ' . 'tmp queue not created');
            }
        }
        return $tmpQueue;
    }

    /**
     * Создает новый обменник.
     * @param string $exchangeName
     * @param array $options [type, passive, durable, auto_delete]
     * @return bool
     *
     * @todo наверное, нужно вынести в класс $options как RabbitWorkerOptions (RabbitExchangeOptions)
     */
    public function createExchange(string $exchangeName, array $options = [])
    {
        return $this->_declareExchange($exchangeName, $options);
    }

    /**
     * Проверяет, существует ли очередь с заданным именем
     *
     * @param string $queueName
     * @return false|string
     *
     * @note неизвестно зачем нам может понадобиться этот метод
     */
    public function checkQueue(string $queueName)
    {
        try {
            return $this->_declareQueue($queueName, ['passive' => true]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Помещает текстовое сообщение в очередь
     *
     * @param string $queueName Название очереди
     * @param string $data Сообщение для отправки в очередь
     * @param string|null $reply_to
     * @param string|null $correlation_id
     * @return bool
     */
    public function pushToQueue(string $queueName, string $data, string $reply_to = null, string $correlation_id = null): bool
    {
        $result = false;

        $options = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        if (isset($reply_to)) {
            $options['reply_to'] = $reply_to;
        }

        if (isset($correlation_id)) {
            $options['correlation_id'] = $correlation_id;
        }

        for ($i = 0; $i < self::PUSH_TRY_COUNT; $i++) {
            try {
                $this->_getChannel()->basic_publish(
                    new AMQPMessage(
                        $data,
                        $options
                    ),
                    '',  //exchange
                    $queueName //routing_key
                /*
                $mandatory = false,
                $immediate = false,
                $ticket = null
                */
                );
                $result = true;
                break; // выходим из цикла
            } catch (Exception $e) {
                // 0.5 sec
                usleep(500000);

                if ($i == (self::PUSH_TRY_COUNT - 1)) {
                    error_log('В очередь ' . $queueName . ' не удалось записать сообщение!');

                    // @todo log Exception via \helper\error analog
                }
            }
        }
        return $result;
    }

    /**
     * Помещает текстовое сообщение в обменник
     *
     * @param string $exchangeName
     * @param string $routingKey
     * @param string $data
     * @return bool
     */
    public function pushToExchange(string $exchangeName, string $routingKey, string $data): bool
    {
        $result = false;

        $options = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        for ($i = 0; $i < self::PUSH_TRY_COUNT; $i++) {
            try {
                $this->_getChannel()->basic_publish(
                    new AMQPMessage(
                        $data,
                        $options
                    ),
                    $exchangeName,  //exchange
                    $routingKey //routing_key
                /*
                $mandatory = false,
                $immediate = false,
                $ticket = null
                */
                );
                $result = true;
                break; // выходим из цикла
            } catch (Exception $e) {
                // 0.5 sec
                usleep(500000);
            }
        }
        return $result;
    }

    /**
     * Отправляет подтверждение об исполнении сообщения.
     * @param AMQPMessage $msg объект от Rabbit
     */
    public function confirm(AMQPMessage $msg): void
    {
        try {
            $msg->get('channel')->basic_ack($msg->get('delivery_tag'));
        } catch (Exception $e) {
            error_log(date('[Y-m-d H:i:s]') . ' ' . __CLASS__ . '::' . __METHOD__ . ' - ' . $e->getMessage());
        }
    }

    /**
     * Отправляет отказ в исполнении сообщения.
     * @param AMQPMessage $msg объект от Rabbit
     * @param boolean $requeue Отправить на повторную обработку
     */
    public function reject(AMQPMessage $msg, bool $requeue = true): void
    {
        try {
            $msg->get('channel')->basic_reject($msg->get('delivery_tag'), $requeue);
        } catch (Exception $e) {
            error_log(date('[Y-m-d H:i:s]') . ' ' . __CLASS__ . '::' . __METHOD__ . ' - ' . $e->getMessage());
        }
    }

    /**
     * Переотправляет сообщение в конец очереди посредством подтверждения и повторной отправки
     * @param AMQPMessage $msg объект от Rabbit
     * @param string $queue очередь, в которую переотправляется сообщение
     */
    public function requeue(AMQPMessage $msg, string $queue): void
    {
        $this->confirm($msg);
        $this->pushToQueue($queue, $msg->getBody());
    }

    /**
     * Вариант обработки сообщения в зависимости 
     *
     * @param AMQPMessage $msg
     * @param RabbitMessageActionEnum $action
     * @param string|null $requeueQueue
     * @return void
     */
    function finalizeMessage(AMQPMessage $msg, RabbitMessageActionEnum $action, string $requeueQueue = null) {
        switch ($action) {
            case RabbitMessageActionEnum::MODE_CONFIRM:
                $this->confirm($msg);
                break;

            case RabbitMessageActionEnum::MODE_REJECT:
                $this->reject($msg, true);
                break;

            case RabbitMessageActionEnum::MODE_REQUEUE:
                $this->requeue($msg, $requeueQueue);
                break;

            case RabbitMessageActionEnum::MODE_DIE:
                $this->reject($msg, true);
                die; //@todo fixme - это убивает весь процесс, а может быть ведь и __after какой-то
        }
    }

    /**
     * Запуск воркера
     *
     * @param string $queue очередь
     * @param Closure $callback обработчик сообщений от rabbit (worker)
     * @param RabbitWorkerOptions $options
     * @todo обрабатывать сигналы
     */
    public function startWorker(string $queue, Closure $callback, RabbitWorkerOptions $options): void
    {
        $qos            = $options->qos;
        $exchange       = $options->exchange;
        $topics         = $options->topics;

        // Если задано название очереди, то создаем с названием, иначе это временная очередь
        if ($queue) {
            $queue = $this->createQueue($queue);
        } else {
            $queue = $this->createTmpQueue();
        }

        // Привязка очереди к exchange
        if ($exchange) {
            $this->createExchange($exchange);

            try {
                foreach ($topics as $binding_key) {
                    $this->_getChannel()->queue_bind($queue, $exchange, $binding_key);
                }
            } catch (Exception $e) {
                error_log('Binding_key = ' . $binding_key . ' exception occurred');

                // может стоит считать, что это critical...
            }
        }

        // Установка QoS
        try {
            $this->_getChannel()->basic_qos(null, $qos, null);
        } catch (Exception $e) {
            error_log('Set QoS = ' . $qos . ' exception occurred');
        }

        // Обработчик потребителя
        $consume_function = function () use ($queue, $callback) {
            $this->_getChannel()->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                $callback
            );
        };

        try {
            $consume_function();
            while ($this->_getChannel()->is_consuming()) {
                try {
                    $this->_getChannel()->wait(null, false, self::CONSUMER_WAIT_TIMEOUT);
                } catch (AMQPTimeoutException $e) {
                    $this->channel->close();
                    $this->channel = null;
                    $consume_function();
                }
            }
            $this->_closeConnection();
        } catch (Throwable $throwable) {
            error_log($throwable->getMessage() . ' in ' . $throwable->getFile() . ' line ' . $throwable->getLine());
            error_log($throwable->getTraceAsString());
        }
    }



    /**
     * Создает новый обменник
     *
     * @param string $exchangeName
     * @param array $options
     * @return bool
     *
     * @note создано по аналогии с _declareQueue, если мы будем создавать разные виды обменников (по типу c createQueue и createTmpQueue)
     */
    private function _declareExchange(string $exchangeName, array $options = [])
    {
        try {
            $this->_getChannel()->exchange_declare(
                $exchangeName,
                $options['type'] ?? AMQPExchangeType::TOPIC,
                $options['passive'] ?? false,
                $options['durable'] ?? true,
                $options['auto_delete'] ?? false
            );
        } catch (Exception) {
            return false;
        }

        return true;
    }

    /**
     * Создает новую очередь с определенными свойствами.
     * Безопасно для повторного вызова, в гайдах вызывают в каждом скрипте (хоть и странно)
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference.html#queue.declare.reserved-1 документация протокола
     * @param string $queueName Название очереди, может быть пустой строкой
     * @param array $options Массив с параметрами очереди
     * @return string|null Название очереди (может отличаться от $queueName)
     */
    private function _declareQueue(string $queueName, array $options = [])
    {
        //если название не указано, rabbitmq генерит гарантированно уникальное. Логично что такие очереди нужно удалять при неиспользовании
        if ($queueName === '') {
            //удаление будет происходит по прошествии времени после отключения последнего консумера (x-expires в миллисекундах)
            $amqptable = new AMQPTable([
                'x-expires' => self::TMP_QUEUE_TTL,
            ]);
        }

        try {
            $answer = $this->_getChannel()->queue_declare(
                $queueName,
                /**
                 * bit passive
                 * If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not.
                 *
                 * The client can use this to check whether an exchange exists without modifying the server state.
                 * When set, all other method fields except name and no-wait are ignored. (!!!)
                 * A declare with both passive and no-wait has no effect. Arguments are compared for semantic equivalence.
                 *
                 * If set, and the exchange does not already exist, the server MUST raise a channel exception with reply code 404 (not found).
                 *
                 * If not set and the exchange exists, the server MUST check that the existing exchange has the same values for type, durable, and arguments fields.
                 * The server MUST respond with Declare-Ok if the requested exchange matches these fields, and MUST raise a channel exception if not.
                 */
                ($options['passive'] ?? false),

                /**
                 * bit durable
                 *
                 * If set when creating a new exchange, the exchange will be marked as durable.
                 * Durable exchanges remain active when a server restarts.
                 * Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
                 */
                ($options['durable'] ?? true),

                /**
                 * bit exclusive
                 *
                 * Exclusive queues may only be accessed by the current connection, and are deleted when that connection closes. Passive declaration of an exclusive queue by other connections are not allowed.
                 *
                 * The server MUST support both exclusive (private) and non-exclusive (shared) queues.
                 * The client MAY NOT attempt to use a queue that was declared as exclusive by another still-open connection.
                 */
                ($options['exclusive'] ?? false),

                /**
                 * bit auto-delete
                 *
                 *    ВАЖНО! Очередь удаляется не когда она пустая и все такое, а тупо когда от неё отключается последний воркер!!!
                 *    Т.е в очереди могут быть записи, воркер почему-то отвалился, очередь убивается.
                 *    Кейс использования навскидку не понятен
                 *
                 * If set, the exchange is deleted when all queues have finished using it.
                 *
                 * The server SHOULD allow for a reasonable delay between the point when it determines that an exchange is not being used (or no longer used),
                 * and the point when it deletes the exchange. At the least it must allow a client to create an exchange and then bind a queue to it, with a small but non-zero delay between these two actions.
                 * The server MUST ignore the auto-delete field if the exchange already exists.
                 */
                ($options['auto_delete'] ?? false),

                /**
                 * no wait
                 */
                false,
                $amqptable ?? []
            );
        } catch (Exception $e) {
            error_log('Не удалось создать очередь ' . $queueName . '!');

            // @todo log Exception via \helper\error analog
            return false;
        }

        return (is_array($answer) ? $answer[0] : $answer);
    }

    /**
     * @throws AMQPIOException
     */
    private function _getConnection($forceReconnect = false): AMQPStreamConnection
    {
        if (empty($this->connection) || $forceReconnect) {
            for ($try = 1; $try <= self::CONNECT_TRY_COUNT; $try++) {
                try {
                    $this->connection = new AMQPStreamConnection(
                        $this->config->host,
                        $this->config->port,
                        $this->config->user,
                        $this->config->password,
                        $this->config->vhost ?? '/',
                        false,
                        'AMQPLAIN',
                        null,
                        'en_US',
                        3.0,
                        self::HEARTBEAT * 3,
                        null,
                        false,
                        self::HEARTBEAT
                    );

                    // no connection
                    if (empty($this->connection)) {
                        throw new Exception('try #' . $try . ' failed', 255);
                    }

                    // connection established
                    break;
                } catch (Exception $connection_exception) {
                    // retry after 0.5s
                    usleep(500000);
                }
            }

            if (empty($this->connection)) {
                throw new Exception('rabbitmq connection failed');
            }
        } else {
            $this->ping();
        }

        return $this->connection;
    }

    //@todo тут прослеживается рекурсия
    public function ping()
    {
        try {
            $this->connection->checkHeartBeat();
        } catch (AMQPHeartbeatMissedException $e) {
            $this->_getConnection(true);
        }
    }

    /**
     * @throws AMQPIOException
     */
    private function _getChannel()
    {
        if (empty($this->channel)) {
            $this->channel = $this->_getConnection()->channel();
            if (empty($this->channel)) {
                throw new Exception('rabbitmq channel failed');
            }
        } else {
            try {
                $this->connection->checkHeartBeat();
            } catch (AMQPHeartbeatMissedException $e) {
                $this->channel = $this->_getConnection(true)->channel();
            }
        }

        return $this->channel;
    }

    /**
     * @throws Exception
     */
    private function _closeConnection()
    {
        if (!empty($this->channel)) {
            $this->channel->close();
            $this->channel = null;
        }

        if (!empty($this->connection)) {
            $this->connection->close();
        }
    }


    /**
     * RPC functionality
     * @todo @fixme хорошенько посмотреть, надо ли нам все это дело
     */

    /**
     * Получение первого сообщения с correlation_id из очереди
     *
     * @param string $queueName
     * @param string $correlation_id
     * @param int $wait_timeout
     * @return string|null
     * @throws ErrorException
     */
    public function getFirstMessage(string $queueName, string $correlation_id, int $wait_timeout): ?string
    {
        $response = null;

        $this->channel->basic_consume(
            $queueName,
            '',
            false,
            true,
            false,
            false,
            function ($msg) use (&$response, $correlation_id) {
                if ($msg->get('correlation_id') == $correlation_id) {
                    $response = $msg->body;
                }
            }
        );

        while (!$response) {
            $this->channel->wait(null, false, $wait_timeout);
        }

        return $response;
    }

    /**
     * @param string $queueName
     * @param string $data
     * @param int $wait_timeout
     * @return string|null
     * @throws ErrorException
     *
     * @todo review!
     */
    public function pushRPC(string $queueName, string $data, int $wait_timeout = self::RPC_WAIT_TIMEOUT): ?string
    {
        $tmp_queue = $this->_declareQueue('');
        $corr_id = uniqid();

        $result = false;
        for ($i = 10; $i > 0; $i--) {
            try {
                $this->_getChannel()->basic_publish(
                    new AMQPMessage(
                        $data,
                        [
                            'correlation_id' => $corr_id,
                            'reply_to' => $tmp_queue,
                        ]
                    ),
                    '',  //exchange
                    $queueName //routing_key
                );
                $result = true;
                break; // выходим из цикла
            } catch (Exception $e) {
                // 0.5 sec
                usleep(500000);
            }
        }

        if (!$result) {
            return null;
        }

        return $this->getFirstMessage($tmp_queue, $corr_id, $wait_timeout);
    }

    /**
     * @param AMQPMessage $message
     * @param string $data
     * @return void
     * @todo review!
     * @todo @old У нас не всегда есть возможность юзать объект AMQPMessage. И оно по больщому счёту и не нужно
     */
    public function replyRPC(AMQPMessage $message, string $data): void
    {
        try {
            $reply_queue = $message->get('reply_to');
        } catch (Exception $e) {
            $reply_queue = false;
        }

        if (!$reply_queue) {
            return;
        }

        try {
            $corr_id = $message->get('correlation_id');
        } catch (Exception $e) {
            $corr_id = '';
        }

        $message->get('channel')->basic_publish(
            new AMQPMessage(
                $data,
                ['correlation_id' => $corr_id]
            ),
            '',
            $reply_queue
        );
    }
}
