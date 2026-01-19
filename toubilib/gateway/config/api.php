<?php

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use toubilib\gateway\api\actions\GateAction;
use toubilib\gateway\middlewares\CorsMiddleware;
use toubilib\gateway\middlewares\AuthnMiddleware;

return [
    GateAction::class => function (ContainerInterface $c) {
        return new GateAction($c->get(ClientInterface::class), $c);
    },
    
    AuthnMiddleware::class => function (ContainerInterface $c) {
        return new AuthnMiddleware($c->get('client.auth'));
    },
    
    CorsMiddleware::class => function (ContainerInterface $c) {
        return new CorsMiddleware(
            allowedOrigins: ['*'],
            allowedMethods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
            maxAge: 3600,
            allowCredentials: false,
            strictMode: false
        );
    },
];