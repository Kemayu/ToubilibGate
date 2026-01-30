<?php

use Psr\Container\ContainerInterface;
use GuzzleHttp\Client;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePatientInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;

use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\infra\repositories\PDORdvRepository;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;
use toubilib\core\application\usecases\ServiceRendezVous;

use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\infra\repositories\PDOPatientRepository;
use toubilib\core\application\usecases\ServicePatient;

use toubilib\core\application\services\AuthzService;
use toubilib\core\application\ports\api\service\AuthzServiceInterface;
use toubilib\infrastructure\adapters\PraticienServiceHttpAdapter;
use toubilib\core\application\ports\spi\EventPublisherInterface;
use toubilib\infra\messaging\RabbitMQEventPublisher;


return [
    // Client Guzzle pour appeler le service praticiens
    'client.praticiens' => function (ContainerInterface $c) {
        return new Client([
            'base_uri' => 'http://app.praticiens',
            'timeout'  => 15.0,
        ]);
    },

    // Service praticiens via adaptateur HTTP
    ServicePraticienInterface::class => function (ContainerInterface $c) {
        return new PraticienServiceHttpAdapter($c->get('client.praticiens'));
    },

    // EventPublisher RabbitMQ
    EventPublisherInterface::class => function (ContainerInterface $c) {
        $host = $_ENV['RABBITMQ_HOST'] ?? 'rabbitmq';
        $port = (int)($_ENV['RABBITMQ_PORT'] ?? 5672);
        $user = $_ENV['RABBITMQ_USER'] ?? 'toubi';
        $password = $_ENV['RABBITMQ_PASSWORD'] ?? 'toubi';
        $exchange = $_ENV['RABBITMQ_EXCHANGE'] ?? 'rdv_events';
        
        return new RabbitMQEventPublisher($host, $port, $user, $password, $exchange);
    },

    // service rendez-vous
    ServiceRendezVousInterface::class => function (ContainerInterface $c) {
        return new ServiceRendezVous(
            $c->get(RdvRepositoryInterface::class),
            $c->get(EventPublisherInterface::class)
        );
    },

    AuthzServiceInterface::class => function (ContainerInterface $c) {
        return new AuthzService($c->get(RdvRepositoryInterface::class));
    },

    'toubirdv.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubirdv.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    'toubiprat.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubiprat.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    'toubipat.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubipat.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    RdvRepositoryInterface::class => fn(ContainerInterface $c) => new PDORdvRepository(
        $c->get('toubirdv.pdo'),  
        $c->get('toubiprat.pdo'),  
        $c->get('toubipat.pdo')    
    ),

    ServicePatientInterface::class => function (ContainerInterface $c) {
        return new ServicePatient($c->get(PatientRepositoryInterface::class));
    },

    PatientRepositoryInterface::class => fn(ContainerInterface $c) => new PDOPatientRepository($c->get('toubipat.pdo')),
];
