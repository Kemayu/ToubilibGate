<?php

use Psr\Container\ContainerInterface;

use toubilib\api\actions\SigninAction;
use toubilib\api\actions\SignupAction;
use toubilib\api\actions\RefreshTokenAction;

use toubilib\core\application\middlewares\AuthnMiddleware;
use toubilib\core\application\middlewares\AuthzMiddleware;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\service\AuthzServiceInterface;

return [
    // application
    SigninAction::class => function (ContainerInterface $c) {
        return new SigninAction($c->get(AuthProviderInterface::class));
    },
    
    SignupAction::class => function (ContainerInterface $c) {
        return new SignupAction($c->get(AuthProviderInterface::class));
    },
    
    RefreshTokenAction::class => function (ContainerInterface $c) {
        return new RefreshTokenAction($c->get(AuthProviderInterface::class));
    },
 
    
    AuthnMiddleware::class => function (ContainerInterface $c) {
        return new AuthnMiddleware($c->get(AuthProviderInterface::class));
    },
    
    AuthzMiddleware::class => function (ContainerInterface $c) {
        return new AuthzMiddleware($c->get(AuthzServiceInterface::class));
    },
  
];