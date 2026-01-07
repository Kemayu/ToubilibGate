<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;

final class AnnulerRdvAction
{
    private ServiceRendezVousInterface $service;

    public function __construct(ServiceRendezVousInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = (string)($args['id'] ?? '');
        if ($id === '') {
            $payload = ['success' => false, 'code' => 'invalid_input', 'message' => 'id manquant'];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        }

        $result = $this->service->annulerRendezVous($id);
        if ($result === null) {
            $payload = ['success' => false, 'code' => 'internal_error', 'message' => 'Erreur interne'];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        if ($result['success'] === true) {
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $status = 422;
        if (isset($result['code']) && $result['code'] === 'rdv_not_found') {
            $status = 404;
        }
        if (isset($result['code']) && $result['code'] === 'rdv_already_cancelled') {
            $status = 409;
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
