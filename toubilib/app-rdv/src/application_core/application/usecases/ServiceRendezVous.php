<?php
declare(strict_types=1);

namespace toubilib\core\application\usecases;

use toubilib\core\application\ports\spi\repositoryInterfaces\ServiceRendezVousInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\application\ports\api\dto\InputRendezVousDTO;
use toubilib\core\domain\entities\rdv\RendezVous;

class ServiceRendezVous implements ServiceRendezVousInterface
{
    private RdvRepositoryInterface $rdvRepository;

    public function __construct(RdvRepositoryInterface $rdvRepository)
    {
        $this->rdvRepository = $rdvRepository;
    }

    public function listerCreneauxPraticien(string $praticienId, string $from, string $to): array
    {
        $rows = $this->rdvRepository->findCreneauxPraticien($praticienId, $from, $to);
        $out = [];
        foreach ($rows as $r) {
            $debutRaw = $r['date_heure_debut'] ?? null;
            $finRaw = $r['date_heure_fin'] ?? null;

            $debut = $debutRaw ? new \DateTimeImmutable($debutRaw) : null;
            $fin = $finRaw ? new \DateTimeImmutable($finRaw) : null;

            $status = isset($r['status']) ? strtolower((string)$r['status']) : '';
            $annule = in_array($status, ['annule', 'annulé', 'cancelled', 'canceled', '1', 'true', 't'], true);

            $patientId = $r['patient_id'] ?? null;

            $out[] = [
                'id' => $r['id'] ?? null,
                'date' => $debut ? $debut->format('Y-m-d') : null,
                'heure_debut' => $debut ? $debut->format('H:i:s') : null,
                'heure_fin' => $fin ? $fin->format('H:i:s') : null,
                'duree' => isset($r['duree']) ? (int)$r['duree'] : null,
                'motif' => $r['motif_visite'] ?? null,
                'annule' => $annule,
                'status' => $r['status'] ?? null,
                'patient_id' => $patientId,
                'patient_email' => $r['patient_email'] ?? null,
                'patient_link' => $patientId ? '/patients/' . $patientId : null,
            ];
        }
        return $out;
    }

    public function getRdvById(string $id): ?array
    {
        return $this->rdvRepository->findById($id);
    }

    public function creerRendezVous(InputRendezVousDTO $dto): array
    {
        $data = $dto->toArray();

        $praticienId = trim((string)($data['praticien_id'] ?? ''));
        $patientId = trim((string)($data['patient_id'] ?? ''));
        if ($praticienId === '') {
            return ['success' => false, 'code' => 'invalid_input', 'message' => 'praticien_id manquant'];
        }
        if ($patientId === '') {
            return ['success' => false, 'code' => 'invalid_input', 'message' => 'patient_id manquant'];
        }

        try {
            $debut = new \DateTimeImmutable($data['date_heure_debut']);
            $fin = $debut->modify('+' . (int)$data['duree'] . ' minutes');
        } catch (\Throwable $e) {
            return ['success' => false, 'code' => 'invalid_datetime', 'message' => 'Date/heure invalide'];
        }

        // empêcher la création d'un RDV dans le passé
        $now = new \DateTimeImmutable();
        if ($debut <= $now) {
            return ['success' => false, 'code' => 'rdv_in_past', 'message' => 'Impossible de créer un RDV dans le passé'];
        }

        // si praticien existe
        if (!$this->rdvRepository->existsPraticienById($data['praticien_id'] ?? '')) {
            return ['success' => false, 'code' => 'praticien_not_found', 'message' => 'Praticien introuvable'];
        }

        // si patient existes 
        if (!$this->rdvRepository->existsPatientById($data['patient_id'] ?? '')) {
            return ['success' => false, 'code' => 'patient_not_found', 'message' => 'Patient introuvable'];
        }

        // motif autorisé pour ce praticien
        $motifs = $this->rdvRepository->getMotifsForPraticien($data['praticien_id'] ?? '');
        $inputMotifRaw = trim((string)($data['motif_visite'] ?? ''));
        $normInput = mb_strtolower($inputMotifRaw, 'UTF-8');
        $found = false;
        if (is_array($motifs) && !empty($motifs)) {
            foreach ($motifs as $m) {
                if (is_array($m)) {
                    if (isset($m['id']) && (string)$m['id'] === $inputMotifRaw) {
                        $found = true;
                        break;
                    }
                    if (isset($m['libelle']) && mb_strtolower(trim((string)$m['libelle']), 'UTF-8') === $normInput) {
                        $found = true;
                        break;
                    }
                } else {
                    if (mb_strtolower(trim((string)$m), 'UTF-8') === $normInput) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                return ['success' => false, 'code' => 'motif_not_allowed', 'message' => 'Motif non autorisé pour ce praticien'];
            }
        }

       //horraire + jour
        $dow = (int)$debut->format('N');
        if ($dow > 5) {
            return ['success' => false, 'code' => 'day_not_allowed', 'message' => 'Jour non ouvré'];
        }
        $boundStart = new \DateTimeImmutable($debut->format('Y-m-d') . ' 08:00:00');
        $boundEnd = new \DateTimeImmutable($debut->format('Y-m-d') . ' 19:00:00');
        if ($debut < $boundStart || $fin > $boundEnd) {
            return ['success' => false, 'code' => 'hour_not_allowed', 'message' => 'Horaire hors plage (08:00-19:00)'];
        }

        // vérifier si le praticien est en indisponibilité (congés, férié, etc.)
        if ($this->rdvRepository->isPraticienIndisponible($data['praticien_id'], $debut, $fin)) {
            return ['success' => false, 'code' => 'praticien_indisponible', 'message' => 'Le praticien est indisponible sur cette période (congés, férié, etc.)'];
        }

        // disponibilité 
        $from = $debut->format('Y-m-d 00:00:00');
        $to = $fin->format('Y-m-d 23:59:59');
        $existing = $this->rdvRepository->findCreneauxPraticien($data['praticien_id'], $from, $to);
        foreach ($existing as $row) {
            $exDebut = new \DateTimeImmutable($row['date_heure_debut']);
            $exFin = !empty($row['date_heure_fin']) ? new \DateTimeImmutable($row['date_heure_fin'])
                : $exDebut->modify('+' . ((int)($row['duree'] ?? 0)) . ' minutes');
            if ($debut < $exFin && $fin > $exDebut) {
                return ['success' => false, 'code' => 'praticien_unavailable', 'message' => 'Praticien indisponible pour ce créneau'];
            }
        }

        // préparation et sauvegarde
        $data['date_heure_debut'] = $debut->format('Y-m-d H:i:s');
        $data['date_heure_fin'] = $fin->format('Y-m-d H:i:s');
        $data['date_creation'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['id'] = bin2hex(random_bytes(16));

        $savedId = $this->rdvRepository->saveRendezVous($data);
        if ($savedId === null) {
            return ['success' => false, 'code' => 'save_failed', 'message' => 'Échec sauvegarde RDV'];
        }

        return ['success' => true, 'id' => $savedId];
    }

    public function annulerRendezVous(string $id): ?array
    {
        $rdv = $this->rdvRepository->findById($id);
        if ($rdv === null) {
            return ['success' => false, 'code' => 'rdv_not_found', 'message' => 'Rendez-vous introuvable'];
        }

        try {
            $rendezVousEntity = new RendezVous($rdv);
            $rendezVousEntity->annuler();
        } catch (\RuntimeException $e) {
            $code = $e->getMessage();
            if ($code === 'rdv_already_cancelled') {
                return ['success' => false, 'code' => 'rdv_already_cancelled', 'message' => 'Rendez-vous déjà annulé'];
            }
            if ($code === 'rdv_in_past') {
                return ['success' => false, 'code' => 'rdv_in_past', 'message' => 'Impossible d\'annuler un RDV passé'];
            }
            return ['success' => false, 'code' => 'annulation_error', 'message' => 'Erreur annulation'];
        }

        $updated = $this->rdvRepository->updateRendezVous($id, ['status' => 1]);
        if (!$updated) {
            return ['success' => false, 'code' => 'save_failed', 'message' => 'Échec sauvegarde annulation'];
        }

        return ['success' => true, 'id' => $id];
    }

    public function updateRdvStatus(string $rdvId, int $newStatus): array
    {
        // vérifier que le status est valide (0-3)
        if (!in_array($newStatus, [0, 1, 2, 3], true)) {
            return ['success' => false, 'code' => 'invalid_status', 'message' => 'Statut invalide (attendu: 0-3)'];
        }

        $rdv = $this->rdvRepository->findById($rdvId);
        if (!$rdv) {
            return ['success' => false, 'code' => 'rdv_not_found', 'message' => 'Rendez-vous introuvable'];
        }

        $currentStatus = (int)($rdv['status'] ?? 0);

        // règles de transition (0 = planifié peut devenir 1/2/3, les autres sont terminaux)
        $allowedTransitions = [
            0 => [1, 2, 3], // planifié → annulé/honoré/non_honoré
            1 => [],        // annulé = terminal
            2 => [],        // honoré = terminal
            3 => [],        // non_honoré = terminal
        ];

        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], true)) {
            return ['success' => false, 'code' => 'invalid_transition', 'message' => "Transition de status $currentStatus vers $newStatus non autorisée"];
        }

        $updated = $this->rdvRepository->updateStatus($rdvId, $newStatus);
        if (!$updated) {
            return ['success' => false, 'code' => 'update_failed', 'message' => 'Échec de la mise à jour'];
        }

        $rdv = $this->rdvRepository->findById($rdvId);
        return ['success' => true, 'rdv' => $rdv];
    }

    public function getHistoriqueConsultations(string $patientId): array
    {
        $rdvs = $this->rdvRepository->getRendezVousByPatientId($patientId);
        
        $dtos = [];
        foreach ($rdvs as $rdv) {
            $dtos[] = \toubilib\core\application\ports\api\dto\RendezVousHistoriqueDTO::fromArray($rdv);
        }
        
        return $dtos;
    }
}
