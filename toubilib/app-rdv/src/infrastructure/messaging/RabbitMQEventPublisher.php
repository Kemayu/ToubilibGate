<?php
declare(strict_types=1);

namespace toubilib\infra\messaging;

use toubilib\core\application\ports\spi\EventPublisherInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

/**
 * Implémentation RabbitMQ du publisher d'événements
 */
class RabbitMQEventPublisher implements EventPublisherInterface
{
    private string $host;
    private int $port;
    private string $user;
    private string $password;
    private string $exchangeName;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $password,
        string $exchangeName = 'rdv_events'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->exchangeName = $exchangeName;
    }

    /**
     * Publie un événement vers RabbitMQ
     */
    public function publish(string $eventName, array $data): void
    {
        try {
            $connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password
            );
            
            $channel = $connection->channel();
            
            // Déclaration de l'exchange (idempotent)
            $channel->exchange_declare(
                $this->exchangeName,
                'topic',
                false,
                true,  // durable
                false
            );
            
            // Création du message
            $messageBody = json_encode($data, JSON_UNESCAPED_UNICODE);
            $message = new AMQPMessage($messageBody, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);
            
            // Publication
            $channel->basic_publish($message, $this->exchangeName, $eventName);
            
            $channel->close();
            $connection->close();
            
        } catch (Exception $e) {
            // En production, logger l'erreur plutôt que de la relancer
            error_log("Erreur publication événement $eventName: " . $e->getMessage());
        }
    }
}
