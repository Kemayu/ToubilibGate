<?php

use Psr\Container\ContainerInterface;
use toubilib\api\actions\ListerPraticienAction;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;
use toubilib\api\actions\PraticienDetailAction;

return [
    // Application - Actions pour les praticiens uniquement
    ListerPraticienAction::class => function (ContainerInterface $c) {
        return new ListerPraticienAction($c->get(ServicePraticienInterface::class));
    },

    PraticienDetailAction::class => function (ContainerInterface $c) {
        return new PraticienDetailAction($c->get(ServicePraticienInterface::class));
    },

];