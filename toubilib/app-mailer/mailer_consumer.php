<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

// RabbitMQ
$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int)(getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'toubi';
$password = getenv('RABBITMQ_PASSWORD') ?: 'toubi';
$queues = ['mail_praticiens', 'mail_patients'];
$exchangeName = 'rdv_events';
$bindings = [
    'mail_praticiens' => 'rdv.*.praticien',
    'mail_patients' => 'rdv.*.patient',
];

// Mailer
$mailerDsn = getenv('MAILER_DSN') ?: 'smtp://mail.toubi:1025';
$mailFrom = getenv('MAIL_FROM') ?: 'no-reply@toubilib.local';
$mailToPatient = getenv('MAIL_TO_PATIENT') ?: 'patient@test.local';
$mailToPraticien = getenv('MAIL_TO_PRATICIEN') ?: 'praticien@test.local';

$transport = Transport::fromDsn($mailerDsn);
$mailer = new Mailer($transport);

echo "Mailer consumer\n";
echo "Queues: " . implode(', ', $queues) . "\n";
echo "SMTP: {$mailerDsn}\n";
echo "En attente de messages...\n\n";

// --- Connexion RabbitMQ avec retry ---
$maxAttempts = 10;
$attempt = 0;
$connected = false;

while (!$connected && $attempt < $maxAttempts) {
    try {
        $connection = new AMQPStreamConnection($host, $port, $user, $password);
        $channel = $connection->channel();
        $connected = true;
    } catch (\Exception $e) {
        $attempt++;
        echo "[" . date('H:i:s') . "] RabbitMQ non disponible, essai $attempt/$maxAttempts...\n";
        sleep(3);
    }
}

if (!$connected) {
    echo "[" . date('H:i:s') . "] Impossible de se connecter à RabbitMQ après $maxAttempts essais\n";
    exit(1);
}

// --- Callback pour traiter les messages ---
$callback = function (AMQPMessage $msg) use ($mailer, $mailFrom, $mailToPatient, $mailToPraticien) {
    $data = json_decode($msg->getBody(), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        echo "[" . date('H:i:s') . "] Message invalide (JSON)\n";
        $msg->ack();
        return;
    }

    $eventType = (string)($data['event_type'] ?? 'event');
    $recipientType = (string)($data['recipient_type'] ?? 'patient');
    if ($recipientType === 'praticien') {
        $to = (string)($data['recipient_email'] ?? $data['praticien_email'] ?? $data['patient_email'] ?? $mailToPraticien);
    } else {
        $to = (string)($data['recipient_email'] ?? $data['patient_email'] ?? $data['praticien_email'] ?? $mailToPatient);
    }

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

    $email = (new Email())
        ->from($mailFrom)
        ->to($to)
        ->subject($subject)
        ->text($body);

    $mailer->send($email);

    echo "[" . date('H:i:s') . "] Mail envoyé à {$to} ({$msg->getRoutingKey()})\n";

    $msg->ack();
};

// --- Déclaration exchange et queues ---
$channel->exchange_declare($exchangeName, 'topic', false, true, false);

foreach ($queues as $queue) {
    $channel->queue_declare($queue, false, true, false, false);
    if (isset($bindings[$queue])) {
        $channel->queue_bind($queue, $exchangeName, $bindings[$queue]);
    }
    $channel->basic_consume($queue, '', false, false, false, false, $callback);
    echo "Abonné à la queue: {$queue}\n";
}

// --- Boucle principale de consommation ---
while ($channel->is_consuming()) {
    $channel->wait();
}

// --- Fermeture propre ---
if (isset($channel)) $channel->close();
if (isset($connection)) $connection->close();
