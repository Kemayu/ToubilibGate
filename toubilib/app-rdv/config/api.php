<?php

use Psr\Container\ContainerInterface;

use toubilib\api\actions\ListerCreneauDejaPraticien;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;
use toubilib\api\actions\ListerRDVbyId;
use toubilib\api\actions\CreateRdvAction;
use toubilib\api\actions\AnnulerRdvAction;
use toubilib\api\actions\UpdateRdvStatusAction;
use toubilib\api\middlewares\ValidateInputRdv;
use toubilib\core\application\middlewares\AuthnMiddleware;
use toubilib\core\application\middlewares\AuthzMiddleware;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\service\AuthzServiceInterface;


return [

    // Actions pour les RDV
    ListerCreneauDejaPraticien::class => function (ContainerInterface $c) {
        return new ListerCreneauDejaPraticien($c->get(ServiceRendezVousInterface::class));
    },
    ListerRDVbyId::class => function (ContainerInterface $c) {
        return new ListerRDVbyId($c->get(ServiceRendezVousInterface::class));
    },
    CreateRdvAction::class => function (ContainerInterface $c) {
        return new CreateRdvAction($c->get(ServiceRendezVousInterface::class));
    },
    AnnulerRdvAction::class => function (ContainerInterface $c) {
        return new AnnulerRdvAction($c->get(ServiceRendezVousInterface::class));
    },
    UpdateRdvStatusAction::class => function (ContainerInterface $c) {
        return new UpdateRdvStatusAction($c->get(ServiceRendezVousInterface::class));
    },
 
  
    // Middlewares
    ValidateInputRdv::class => function (ContainerInterface $c) {
        return new ValidateInputRdv();
    },
    // AuthnMiddleware::class => function (ContainerInterface $c) {
    //     return new AuthnMiddleware();
    // },
    // AuthzMiddleware::class => function (ContainerInterface $c) {
    //     return new AuthzMiddleware($c->get(AuthzServiceInterface::class));
    // },
    
];