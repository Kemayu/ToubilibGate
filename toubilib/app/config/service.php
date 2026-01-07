<?php

use Psr\Container\ContainerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePatientInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;
use toubilib\core\application\usecases\ServicePraticien;
use toubilib\infra\repositories\PDOPraticienRepository;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\infra\repositories\PDORdvRepository;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;
use toubilib\core\application\usecases\ServiceRendezVous;

// nouveaux imports pour patient
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\infra\repositories\PDOPatientRepository;
use toubilib\core\application\usecases\ServicePatient;

use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;
use toubilib\infra\repositories\PDOAuthRepository;
use toubilib\core\application\services\ToubilibAuthnService;
use toubilib\core\application\services\AuthzService;
use toubilib\infra\provider\JwtAuthProvider;
use toubilib\infra\jwt\JwtManager;
use toubilib\core\application\ports\api\service\ToubilibAuthnServiceInterface;
use toubilib\core\application\ports\api\service\AuthzServiceInterface;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\jwt\JwtManagerInterface;

return [
        // service
    ServicePraticienInterface::class => function (ContainerInterface $c) {
        return new ServicePraticien($c->get(PDOPraticienRepository::class));
    },
        // service rendez-vous
    ServiceRendezVousInterface::class => function (ContainerInterface $c) {
        return new ServiceRendezVous($c->get(RdvRepositoryInterface::class));
    },

        // service patient
 
    ServicePatientInterface::class => function (ContainerInterface $c) {
        return new ServicePatient($c->get(PatientRepositoryInterface::class));
    },

    AuthRepositoryInterface::class => fn(ContainerInterface $c) => new PDOAuthRepository($c->get('toubiauth.pdo')),
    
    JwtManagerInterface::class => function (ContainerInterface $c) {
        return new JwtManager(
            $_ENV['JWT_SECRET'],
            'toubilib.api',
            'HS512',
            900,
            2592000
        );
    },
    
    ToubilibAuthnServiceInterface::class => function (ContainerInterface $c) {
        return new ToubilibAuthnService($c->get(AuthRepositoryInterface::class));
    },
    
    AuthProviderInterface::class => function (ContainerInterface $c) {
        return new JwtAuthProvider(
            $c->get(ToubilibAuthnServiceInterface::class),
            $c->get(JwtManagerInterface::class)
        );
    },
    
    AuthzServiceInterface::class => function (ContainerInterface $c) {
        return new AuthzService($c->get(RdvRepositoryInterface::class));
    },



    // infra
    'toubiprat.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubiprat.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    'toubiauth.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubiauth.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    'toubirdv.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubirdv.db.config'));
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

    PDOPraticienRepository::class => fn(ContainerInterface $c) => new PDOPraticienRepository($c->get('toubiprat.pdo')),
    
    RdvRepositoryInterface::class => fn(ContainerInterface $c) => new PDORdvRepository(
        $c->get('toubirdv.pdo'),  
        $c->get('toubiprat.pdo'),  
        $c->get('toubipat.pdo')    
    ),

    PDOPatientRepository::class => fn(ContainerInterface $c) => new PDOPatientRepository($c->get('toubipat.pdo')),

    PatientRepositoryInterface::class => fn(ContainerInterface $c) => new PDOPatientRepository($c->get('toubipat.pdo')),
];