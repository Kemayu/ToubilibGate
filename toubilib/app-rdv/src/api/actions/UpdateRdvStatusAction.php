<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;

class UpdateRdvStatusAction extends AbstractAction
{
    private ServiceRendezVousInterface $service;

    public function __construct(ServiceRendezVousInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $id = (string)($args['id'] ?? '');
        $data = (array)$rq->getParsedBody();
        
        // accepter "status" en string ou int
        $statusRaw = $data['status'] ?? null;
        
        // mapping string → int
        $statusMap = [
            'planifie' => 0,
            'annule' => 1,
            'honore' => 2,
            'non_honore' => 3,
        ];
        
        if (is_string($statusRaw) && isset($statusMap[$statusRaw])) {
            $status = $statusMap[$statusRaw];
        } elseif (is_numeric($statusRaw) && in_array((int)$statusRaw, [0, 1, 2, 3], true)) {
            $status = (int)$statusRaw;
        } else {
            $rs->getBody()->write(json_encode([
                'error' => 'invalid_status',
                'message' => 'Le statut doit être : 0 (planifié), 1 (annulé), 2 (honoré), 3 (non honoré), ou les équivalents string'
            ], JSON_UNESCAPED_UNICODE));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $result = $this->service->updateRdvStatus($id, $status);

        if (!$result['success']) {
            $statusCode = match($result['code']) {
                'rdv_not_found' => 404,
                'invalid_transition' => 409,
                default => 500,
            };
            $rs->getBody()->write(json_encode([
                'error' => $result['code'],
                'message' => $result['message']
            ], JSON_UNESCAPED_UNICODE));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
        }

        $rdv = $result['rdv'] ?? [];
        $rs->getBody()->write(json_encode([
            'id' => $rdv['id'],
            'status' => (int)($rdv['status'] ?? 0),
            '_links' => [
                'self' => ['href' => '/rdvs/' . $rdv['id']],
                'praticien' => ['href' => '/praticiens/' . ($rdv['praticien_id'] ?? '')],
                'patient' => ['href' => '/patients/' . ($rdv['patient_id'] ?? '')],
            ]
        ], JSON_UNESCAPED_UNICODE));
        return $rs->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}