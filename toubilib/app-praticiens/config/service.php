<?php

use Psr\Container\ContainerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;
use toubilib\core\application\usecases\ServicePraticien;
use toubilib\infra\repositories\PDOPraticienRepository;

return [
    // Service Praticien
    ServicePraticienInterface::class => function (ContainerInterface $c) {
        return new ServicePraticien($c->get(PDOPraticienRepository::class));
    },

    // Infrastructure DB
    'toubiprat.pdo' => function (ContainerInterface $c) {
        $config = parse_ini_file($c->get('toubiprat.db.config'));
        $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']}";
        $user = $config['username'];
        $password = $config['password'];
        return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    },

    PDOPraticienRepository::class => fn(ContainerInterface $c) => new PDOPraticienRepository($c->get('toubiprat.pdo')),
];
