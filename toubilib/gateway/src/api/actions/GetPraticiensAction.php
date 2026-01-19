<?php
declare(strict_types=1);

namespace toubilib\gateway\api\actions;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetPraticiensAction extends AbstractGatewayAction
{
    public function __invoke(ServerRequestInterface $request,ResponseInterface $response, array $args):  ResponseInterface
    {
        // Interroger l'API toubilib
        $apiResponse = $this->remote_service->request('GET','/praticiens');

        // Récupérer le corps de la réponse
        $body = $apiResponse->getBody();

        // Renvoyer la réponse
        $response->getBody()->write((string)$body);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($apiResponse->getStatusCode());
    }
}
