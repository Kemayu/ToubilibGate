<?php
declare(strict_types=1);

use Slim\App;
use toubilib\gateway\api\actions\GateAction;
use toubilib\gateway\middlewares\AuthnMiddleware;

return function (App $app): App {
    // Routes pour les praticiens (vers app.praticiens)
    $app->get('/praticiens', GateAction::class);
    $app->get('/praticiens/{id}', GateAction::class);
    
    // Routes pour les rendez-vous (vers app.rdv)
    $app->get('/rdvs/{id}', GateAction::class)->add(AuthnMiddleware::class);
    $app->post('/rdvs', GateAction::class)->add(AuthnMiddleware::class);
    $app->delete('/rdvs/{id}', GateAction::class)->add(AuthnMiddleware::class);
    $app->patch('/rdvs/{id}', GateAction::class)->add(AuthnMiddleware::class);
    $app->get('/praticiens/{praticienId}/creneaux', GateAction::class)->add(AuthnMiddleware::class);
    
    // Routes pour les patients (vers app.rdv)
    $app->get('/patients', GateAction::class);
    $app->get('/patients/{id}', GateAction::class);
    $app->get('/patients/{patientId}/consultations', GateAction::class)->add(AuthnMiddleware::class);
    
    // Routes pour l'authentification (vers app.auth)
    $app->post('/auth/signin', GateAction::class);
    $app->post('/auth/signup', GateAction::class);
    $app->post('/auth/refresh', GateAction::class);
    
    return $app;
};
