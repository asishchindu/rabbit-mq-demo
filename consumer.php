<?php

require __DIR__ . '/vendor/autoload.php';

use App\Rabbit;
use PhpAmqpLib\Message\AMQPMessage;

$logFile = '/app/data/processed.log';

$connection = Rabbit::connect();
$channel = $connection->channel();
Rabbit::declareQueue($channel);

// hand each worker one message at a time
$channel->basic_qos(0, 1, false);

echo "[*] Waiting for messages on '" . Rabbit::QUEUE . "'. Press CTRL+C to exit.\n";

$callback = function (AMQPMessage $msg) use ($logFile) {
    $data = json_decode($msg->getBody(), true);
    echo "[x] Received: {$data['text']}\n";

    sleep(1); // simulate work

    $line = sprintf('%s  %s  (sent %s)', date('H:i:s'), strtoupper($data['text']), $data['sent_at']);
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);

    $msg->ack();
    echo "[✓] Done\n";
};

$channel->basic_consume(Rabbit::QUEUE, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}
