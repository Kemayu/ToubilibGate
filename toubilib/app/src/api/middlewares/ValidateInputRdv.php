<?php
declare(strict_types=1);

namespace toubilib\api\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use toubilib\core\application\ports\api\dto\InputRendezVousDTO;

final class ValidateInputRdv implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = (array)$request->getParsedBody();

        $errors = [];

        $praticien = trim((string)($data['praticien_id'] ?? $data['praticienId'] ?? ''));
        $patient = trim((string)($data['patient_id'] ?? $data['patientId'] ?? ''));
        $dateRaw = trim((string)($data['date_heure_debut'] ?? $data['dateHeureDebut'] ?? ''));
        $motif = trim((string)($data['motif_visite'] ?? $data['motifVisite'] ?? ''));
        $duree = isset($data['duree']) ? (int)$data['duree'] : 30;

        if ($praticien === '') {
            $errors[] = 'praticien_id est requis';
        }
        if ($patient === '') {
            $errors[] = 'patient_id est requis';
        }
        if ($dateRaw === '') {
            $errors[] = 'date_heure_debut est requis';
        } else {
            try {
                new \DateTimeImmutable($dateRaw);
            } catch (\Throwable $e) {
                $errors[] = 'date_heure_debut invalide';
            }
        }

        if ($duree <= 0 || $duree > 24 * 60) {
            $errors[] = 'duree invalide';
        }

        $datasorted = [
            'praticien_id' => $praticien,
            'patient_id' => $patient,
            'date_heure_debut' => $dateRaw,
            'motif_visite' => strip_tags($motif),
            'duree' => $duree,
        ];

        if (!empty($errors)) {
            $resp = new Response(422);
            $resp->getBody()->write(json_encode(['errors' => $errors], JSON_UNESCAPED_UNICODE));
            return $resp->withHeader('Content-Type', 'application/json');
        }

        $dto = InputRendezVousDTO::fromArray($datasorted);
        $request = $request->withAttribute('inputRdv', $dto);

        return $handler->handle($request);
    }
}
