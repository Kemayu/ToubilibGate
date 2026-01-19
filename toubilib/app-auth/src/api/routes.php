<?php
declare(strict_types=1);

use Slim\App;

use toubilib\api\actions\SignupAction;
use toubilib\api\actions\SigninAction;
use toubilib\api\actions\RefreshTokenAction;

return function (App $app): App {

    $app->post('/auth/signin', SigninAction::class)->setName('auth.signin');
    $app->post('/auth/signup', SignupAction::class)->setName('auth.signup');
    $app->post('/auth/refresh', RefreshTokenAction::class)->setName('auth.refresh');

    return $app;
};
