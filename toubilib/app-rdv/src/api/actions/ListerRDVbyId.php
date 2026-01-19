<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;

class ListerRDVbyId extends AbstractAction
{
    private ServiceRendezVousInterface $serviceRendezVous;

    public function __construct(ServiceRendezVousInterface $serviceRendezVous)
    {
        $this->serviceRendezVous = $serviceRendezVous;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $id = trim((string)($args['id'] ?? ''));

        if ($id === '') {
            $rs->getBody()->write(json_encode([
                'error' => 'invalid_id',
                'message' => 'L\'identifiant du rendez-vous est requis'
            ], JSON_UNESCAPED_UNICODE));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $rdv = $this->serviceRendezVous->getRdvById($id);

        if ($rdv === null) {
            $rs->getBody()->write(json_encode([
                'error' => 'rdv_not_found',
                'message' => 'Rendez-vous introuvable'
            ], JSON_UNESCAPED_UNICODE));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $status = (int)($rdv['status'] ?? 0);
        $praticienId = (string)($rdv['praticien_id'] ?? '');
        $patientId = (string)($rdv['patient_id'] ?? '');

        $response = [
            'type' => 'resource',
            'rendez-vous' => [
                'id' => $rdv['id'],
                'praticien_id' => $praticienId,
                'patient_id' => $patientId,
                'patient_email' => $rdv['patient_email'] ?? null,
                'date_heure_debut' => $rdv['date_heure_debut'] ?? null,
                'date_heure_fin' => $rdv['date_heure_fin'] ?? null,
                'duree' => (int)($rdv['duree'] ?? 0),
                'status' => $status,
                'status_label' => $this->getStatusLabel($status),
                'motif_visite' => $rdv['motif_visite'] ?? null,
                'date_creation' => $rdv['date_creation'] ?? null,
            ],
            'links' => [
                'self' => [
                    'href' => '/rdvs/' . $rdv['id']
                ],
                'praticien' => [
                    'href' => '/praticiens/' . $praticienId
                ],
                'patient' => [
                    'href' => '/patients/' . $patientId
                ],
            ]
        ];

        if ($status === 0) {
            $response['links']['annuler'] = [
                'href' => '/rdvs/' . $rdv['id'],
                'method' => 'PATCH',
                'body' => ['status' => 1],
                'description' => 'Annuler ce rendez-vous'
            ];
            $response['links']['honorer'] = [
                'href' => '/rdvs/' . $rdv['id'],
                'method' => 'PATCH',
                'body' => ['status' => 2],
                'description' => 'Marquer comme honoré (patient présent)'
            ];
            $response['links']['ne_pas_honorer'] = [
                'href' => '/rdvs/' . $rdv['id'],
                'method' => 'PATCH',
                'body' => ['status' => 3],
                'description' => 'Marquer comme non honoré (patient absent)'
            ];
        }

        $rs->getBody()->write(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $rs->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    /**
     * Convertit le code statut en libellé lisible
     */
    private function getStatusLabel(int $status): string
    {
        return match($status) {
            0 => 'planifié',
            1 => 'annulé',
            2 => 'honoré',
            3 => 'non honoré',
            default => 'inconnu',
        };
    }
}
