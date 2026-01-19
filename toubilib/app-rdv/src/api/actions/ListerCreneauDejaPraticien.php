<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;

class ListerCreneauDejaPraticien extends AbstractAction
{
    protected ServiceRendezVousInterface $serviceRendezVous;

    public function __construct(ServiceRendezVousInterface $serviceRendezVous)
    {
        $this->serviceRendezVous = $serviceRendezVous;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'] ?? $rq->getQueryParams()['praticienId'] ?? null;
        $from = $rq->getQueryParams()['from'] ?? null;
        $to = $rq->getQueryParams()['to'] ?? null;

        if (!$praticienId) {
            $rs->getBody()->write(json_encode([
                'error' => 'missing_parameter',
                'message' => 'Le paramètre praticienId est requis'
            ], JSON_UNESCAPED_UNICODE));
            return $rs->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // si from/to manquant => période = journée courante
        if (!$from || !$to) {
            $today = new \DateTimeImmutable('now');
            $from = $today->format('Y-m-d') . ' 00:00:00';
            $to = $today->format('Y-m-d') . ' 23:59:59';
        }

        $creneaux = $this->serviceRendezVous->listerCreneauxPraticien($praticienId, $from, $to);

        $creneauxHateoas = [];
        foreach ($creneaux as $creneau) {
            $rdvId = $creneau['id'] ?? null;
            $patientId = $creneau['patient_id'] ?? null;
            $status = (int)($creneau['status'] ?? 0);

            $item = [
                'id' => $rdvId,
                'date' => $creneau['date'] ?? null,
                'heure_debut' => $creneau['heure_debut'] ?? null,
                'heure_fin' => $creneau['heure_fin'] ?? null,
                'duree' => $creneau['duree'] ?? null,
                'motif' => $creneau['motif'] ?? null,
                'status' => $status,
                'patient_id' => $patientId,
                'patient_email' => $creneau['patient_email'] ?? null,
                'links' => [
                    'self' => ['href' => '/rdvs/' . $rdvId],
                    'patient' => ['href' => '/patients/' . $patientId]
                ]
            ];

            if ($status === 0) {
                $item['links']['annuler'] = [
                    'href' => '/rdvs/' . $rdvId,
                    'method' => 'PATCH',
                    'body' => ['status' => 1]
                ];
                $item['links']['honorer'] = [
                    'href' => '/rdvs/' . $rdvId,
                    'method' => 'PATCH',
                    'body' => ['status' => 2]
                ];
                $item['links']['ne_pas_honorer'] = [
                    'href' => '/rdvs/' . $rdvId,
                    'method' => 'PATCH',
                    'body' => ['status' => 3]
                ];
            }

            $creneauxHateoas[] = $item;
        }

        $response = ['data' => $creneauxHateoas];

        $rs->getBody()->write(json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $rs->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}