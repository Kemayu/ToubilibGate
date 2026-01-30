<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

echo "Test RabbitMQ...\n";

try {
    $connection = new AMQPStreamConnection('rabbitmq', 5672, 'toubi', 'toubi');
    $channel = $connection->channel();
    
    $channel->exchange_declare('rdv_events', 'topic', false, true, false);
    $channel->queue_declare('mail_praticiens', false, true, false, false);
    $channel->queue_declare('mail_patients', false, true, false, false);
    $channel->queue_bind('mail_praticiens', 'rdv_events', 'rdv.*.praticien');
    $channel->queue_bind('mail_patients', 'rdv_events', 'rdv.*.patient');
    
    $msg = new AMQPMessage(json_encode(['test' => 'ok']), ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'rdv_events', 'rdv.created.patient');
    
    $channel->close();
    $connection->close();
    
    echo "Test OK\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}