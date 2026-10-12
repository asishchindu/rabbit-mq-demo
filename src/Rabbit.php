<?php

namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;

final class Rabbit
{
    public const QUEUE = 'tasks';

    public static function connect(int $retries = 10): AMQPStreamConnection
    {
        $host = getenv('RABBITMQ_HOST') ?: 'localhost';
        $port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
        $user = getenv('RABBITMQ_USER') ?: 'guest';
        $pass = getenv('RABBITMQ_PASS') ?: 'guest';

        for ($attempt = 1; ; $attempt++) {
            try {
                return new AMQPStreamConnection($host, $port, $user, $pass);
            } catch (\Throwable $e) {
                if ($attempt >= $retries) {
                    throw $e;
                }
                fwrite(STDERR, "RabbitMQ not ready ({$e->getMessage()}), retrying in 2s...\n");
                sleep(2);
            }
        }
    }

    public static function declareQueue($channel): void
    {
        // durable queue so messages survive a broker restart
        $channel->queue_declare(self::QUEUE, false, true, false, false);
    }
}
