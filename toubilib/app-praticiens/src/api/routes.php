<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use toubilib\api\actions\ListerPraticienAction;
use toubilib\api\actions\PraticienDetailAction;

return function (App $app): App {
    
    // Routes pour les praticiens
    $app->get('/praticiens', ListerPraticienAction::class)
        ->setName('praticiens.list');
    
    $app->get('/praticiens/{id}', PraticienDetailAction::class)
        ->setName('praticiens.detail');
    
    // Preflight CORS
    $app->options('/{routes:.+}', function (
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $response;
    });

    return $app;
};
