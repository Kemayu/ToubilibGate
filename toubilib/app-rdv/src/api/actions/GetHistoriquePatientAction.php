<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;

class GetHistoriquePatientAction extends AbstractAction
{
    private ServiceRendezVousInterface $serviceRendezVous;

    public function __construct(ServiceRendezVousInterface $serviceRendezVous)
    {
        $this->serviceRendezVous = $serviceRendezVous;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $patientId = trim((string)($args['patientId'] ?? ''));

        if ($patientId === '') {
            $rs->getBody()->write(json_encode([
                'error' => 'invalid_patient_id',
                'message' => 'L\'identifiant du patient est requis'
            ], JSON_UNESCAPED_UNICODE));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $consultations = $this->serviceRendezVous->getHistoriqueConsultations($patientId);

        // Construire la réponse avec HATEOAS
        $consultationsArray = [];
        foreach ($consultations as $consultation) {
            $data = $consultation->toArray();
            
            // Ajouter les liens HATEOAS pour chaque consultation
            $data['links'] = [
                'self' => [
                    'href' => '/rdvs/' . $data['id']
                ],
                'praticien' => [
                    'href' => '/praticiens/' . $data['praticien']['id']
                ],
            ];

            // Si le RDV est planifié (status = 0), ajouter les actions possibles
            if ($data['status'] === 0) {
                $data['links']['annuler'] = [
                    'href' => '/rdvs/' . $data['id'],
                    'method' => 'PATCH',
                    'body' => ['status' => 1],
                    'description' => 'Annuler ce rendez-vous'
                ];
            }

            $consultationsArray[] = $data;
        }

        $response = [
            'type' => 'collection',
            'count' => count($consultationsArray),
            'consultations' => $consultationsArray,
            'links' => [
                'self' => [
                    'href' => '/patients/' . $patientId . '/consultations'
                ],
                'patient' => [
                    'href' => '/patients/' . $patientId
                ],
            ]
        ];

        $rs->getBody()->write(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $rs->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
