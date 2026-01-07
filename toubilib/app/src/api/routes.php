<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use toubilib\api\actions\AnnulerRdvAction;
use toubilib\api\actions\CreateRdvAction;
use toubilib\api\actions\GetHistoriquePatientAction;
use toubilib\api\actions\ListerPatientAction;
use toubilib\api\actions\ListerPraticienAction;
use toubilib\api\actions\ListerCreneauDejaPraticien;
use toubilib\api\actions\ListerRDVbyId;
use toubilib\api\actions\PatientDetailAction;
use toubilib\api\actions\PraticienDetailAction;
use toubilib\api\actions\UpdateRdvStatusAction;
use toubilib\api\actions\SignupAction;
use toubilib\api\actions\SigninAction;
use toubilib\api\actions\RefreshTokenAction;

use toubilib\api\middlewares\ValidateInputRdv;


use toubilib\core\application\middlewares\AuthnMiddleware;
use toubilib\core\application\middlewares\AuthzMiddleware;

return function (App $app): App {

    $app->post('/auth/signin', SigninAction::class)->setName('auth.signin');
    $app->post('/auth/signup', SignupAction::class)->setName('auth.signup');
    $app->post('/auth/refresh', RefreshTokenAction::class)->setName('auth.refresh');

    $app->get('/praticiens', ListerPraticienAction::class);
    $app->get('/praticiens/{id}', PraticienDetailAction::class);
    $app->get('/patients', ListerPatientAction::class);
    $app->get('/patients/{id}', PatientDetailAction::class);
    
    // Opération 11: historique patient (authentification requise)
    $app->get('/patients/{patientId}/consultations', GetHistoriquePatientAction::class)
        ->setName('patient.consultations')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // Opération 7: agenda praticien (authentification requise)
    $app->get('/praticiens/{praticienId}/creneaux', ListerCreneauDejaPraticien::class)
        ->setName('agenda')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);
    
    // Opération 4,6: consulter un RDV (authentification requise)
    $app->get('/rdvs/{id}', ListerRDVbyId::class)
        ->setName('rdv.get')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);
    
    // Opération 6: créer un RDV (authentification requise - patient seulement)
    $app->post('/rdvs', CreateRdvAction::class)
        ->setName('rdv.create')
        ->add(ValidateInputRdv::class)
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);
    
    // Opération 5: annuler un RDV (authentification requise - patient du RDV seulement)
    $app->delete('/rdvs/{id}', AnnulerRdvAction::class)
        ->setName('rdv.delete')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

    // Opération 10: modifier statut RDV (authentification requise - praticien du RDV seulement)
    $app->patch('/rdvs/{id}', UpdateRdvStatusAction::class)
        ->setName('rdv.update')
        ->add(AuthzMiddleware::class)
        ->add(AuthnMiddleware::class);

   
    // Preflight CORS
    $app->options('/{routes:.+}', function (
        ServerRequestInterface $request,
        ResponseInterface $response
        ): ResponseInterface {
        return $response;
    });

    return $app;
};
