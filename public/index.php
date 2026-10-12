<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Rabbit;
use PhpAmqpLib\Message\AMQPMessage;

$logFile = '/app/data/processed.log';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['message'] ?? '');
    if ($text !== '') {
        $connection = Rabbit::connect(1);
        $channel = $connection->channel();
        Rabbit::declareQueue($channel);

        $payload = json_encode(['text' => $text, 'sent_at' => date('c')]);
        $msg = new AMQPMessage($payload, [
            'content_type'  => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);
        $channel->basic_publish($msg, '', Rabbit::QUEUE);

        $channel->close();
        $connection->close();
        $flash = "Published: {$text}";
    }
}

$processed = is_file($logFile) ? array_reverse(file($logFile, FILE_IGNORE_NEW_LINES)) : [];
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>RabbitMQ PHP Demo</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 40px auto; padding: 0 16px; }
        input[type=text] { width: 70%; padding: 8px; }
        button { padding: 8px 16px; }
        .flash { background: #e6ffed; padding: 8px; border: 1px solid #b7ebc6; }
        li { font-family: monospace; margin: 4px 0; }
    </style>
</head>
<body>
    <h1>RabbitMQ PHP Demo</h1>
    <?php if ($flash): ?><p class="flash"><?= htmlspecialchars($flash) ?></p><?php endif; ?>
    <form method="post">
        <input type="text" name="message" placeholder="Type a message" autofocus>
        <button type="submit">Send</button>
    </form>

    <h2>Processed by consumer <small><a href="/">refresh</a></small></h2>
    <ul>
        <?php foreach ($processed as $line): ?>
            <li><?= htmlspecialchars($line) ?></li>
        <?php endforeach; ?>
        <?php if (!$processed): ?><li>Nothing yet.</li><?php endif; ?>
    </ul>
    <p>RabbitMQ management UI: <a href="http://localhost:15672">localhost:15672</a> (guest / guest)</p>
</body>
</html>
