<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use toubilib\gateway\middlewares\CorsMiddleware;

$builder = new ContainerBuilder();
$settings = require_once __DIR__ . '/settings.php';
$builder->addDefinitions([
    'settings' => $settings
]);
$builder->addDefinitions(__DIR__ . '/service.php');
$builder->addDefinitions(__DIR__ . '/api.php');

$c=$builder->build();
$app = AppFactory::createFromContainer($c);


$app->addBodyParsingMiddleware();
$app->add(CorsMiddleware::class);
$app->addRoutingMiddleware();

// Ajouter le middleware CORS
$app->add($c->get(CorsMiddleware::class));

// Ajouter le middleware d'erreurs
$app->addErrorMiddleware(true, true, true);

$routes = require_once __DIR__ . '/../src/api/routes.php';
if (is_callable($routes)) {
    $app = $routes($app);
}

return $app;