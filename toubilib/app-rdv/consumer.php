<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use toubilib\core\application\ports\spi\MailerInterface;
use toubilib\infra\mailer\SymfonyMailerAdapter;

// Configuration RabbitMQ
$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int)(getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'toubi';
$password = getenv('RABBITMQ_PASSWORD') ?: 'toubi';

// Queues à consommer
$queues = ['mail_praticiens', 'mail_patients'];

// Configuration Mailer
$mailerDsn = getenv('MAILER_DSN') ?: 'smtp://mail.toubi:1025';
$mailerFrom = getenv('MAIL_FROM') ?: 'no-reply@toubilib.local';
$mailToPatient = getenv('MAIL_TO_PATIENT') ?: 'patient@test.local';
$mailToPraticien = getenv('MAIL_TO_PRATICIEN') ?: 'praticien@test.local';

echo "Consommateur RabbitMQ (mail)\n";
echo "Queues: " . implode(', ', $queues) . "\n";
echo "SMTP: {$mailerDsn}\n";
echo "En attente de messages...\n\n";

try {
    // Connexion à RabbitMQ
    $connection = new AMQPStreamConnection($host, $port, $user, $password);
    $channel = $connection->channel();

    // Mailer
    /** @var MailerInterface $mailer */
    $mailer = new SymfonyMailerAdapter($mailerDsn, $mailerFrom);

    // Callback pour traiter les messages
    $callback = function (AMQPMessage $msg) use ($mailer, $mailToPatient, $mailToPraticien) {
        $data = json_decode($msg->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            echo "[" . date('H:i:s') . "] Message invalide (JSON)\n";
            $msg->ack();
            return;
        }

        $eventType = (string)($data['event_type'] ?? 'event');
        $recipientType = (string)($data['recipient_type'] ?? 'patient');
        $to = (string)($data['recipient_email']
            ?? $data['patient_email']
            ?? $data['praticien_email']
            ?? ($recipientType === 'praticien' ? $mailToPraticien : $mailToPatient));

        $subject = "RDV {$eventType} ({$recipientType})";
        $body = "Bonjour,\n\n"
            . "Un rendez-vous a été {$eventType}.\n"
            . "RDV: " . ($data['rdv_id'] ?? 'N/A') . "\n"
            . "Praticien: " . ($data['praticien_id'] ?? 'N/A') . "\n"
            . "Patient: " . ($data['patient_id'] ?? 'N/A') . "\n"
            . "Date début: " . ($data['date_heure_debut'] ?? 'N/A') . "\n"
            . "Date fin: " . ($data['date_heure_fin'] ?? 'N/A') . "\n"
            . "Motif: " . ($data['motif'] ?? 'N/A') . "\n\n"
            . "--\nToubilib";

        $mailer->send($to, $subject, $body);

        echo "[" . date('H:i:s') . "] Mail envoyé à {$to} ({$msg->getRoutingKey()})\n";

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
