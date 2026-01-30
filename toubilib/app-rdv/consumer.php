<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Configuration RabbitMQ
$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int)(getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'toubi';
$password = getenv('RABBITMQ_PASSWORD') ?: 'toubi';

// Queues à consommer
$queues = ['mail_praticiens', 'mail_patients'];

echo "=== Consommateur de messages RabbitMQ ===\n";
echo "Connexion à RabbitMQ: {$host}:{$port}\n";
echo "Queues surveillées: " . implode(', ', $queues) . "\n";
echo "En attente de messages... (CTRL+C pour arrêter)\n\n";

try {
    // Connexion à RabbitMQ
    $connection = new AMQPStreamConnection($host, $port, $user, $password);
    $channel = $connection->channel();

    // Callback pour traiter les messages
    $callback = function (AMQPMessage $msg) {
        echo str_repeat('=', 70) . "\n";
        echo "Message reçu à " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('-', 70) . "\n";
        
        // Afficher les informations de routage
        echo "Queue: {$msg->getDeliveryTag()}\n";
        echo "Routing Key: {$msg->getRoutingKey()}\n";
        echo "Exchange: {$msg->getExchange()}\n";
        
        // Décoder et afficher le contenu
        $data = json_decode($msg->getBody(), true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "\nContenu du message:\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
            // Extraction des informations clés
            if (isset($data['event_type'])) {
                echo "\nType d'événement: " . strtoupper($data['event_type']) . "\n";
            }
            if (isset($data['rdv_id'])) {
                echo "RDV ID: {$data['rdv_id']}\n";
            }
            if (isset($data['recipient_type'])) {
                echo "Destinataire: " . ucfirst($data['recipient_type']) . "\n";
            }
            if (isset($data['motif'])) {
                echo "Motif: {$data['motif']}\n";
            }
            if (isset($data['date_heure_debut'])) {
                echo "Date: {$data['date_heure_debut']}\n";
            }
        } else {
            echo $msg->getBody();
        }
        
        echo str_repeat('=', 70) . "\n\n";
        
        // Acquitter le message
        $msg->ack();
    };

    // S'abonner aux queues
    foreach ($queues as $queue) {
        // Déclarer la queue (si elle n'existe pas déjà)
        $channel->queue_declare(
            $queue,      
            false,      
            true,        
            false,       
            false        
        );
        
        // Consommer les messages
        $channel->basic_consume(
            $queue,      
            '',          
            false,       
            false,       
            false,       
            false,       
            $callback    
        );
        
        echo "Abonné à la queue: {$queue}\n";
    }

    echo "\nConsommation en cours...\n\n";

    // Boucle de consommation
    while ($channel->is_consuming()) {
        $channel->wait();
    }

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (isset($channel)) {
        $channel->close();
    }
    if (isset($connection)) {
        $connection->close();
    }
}
