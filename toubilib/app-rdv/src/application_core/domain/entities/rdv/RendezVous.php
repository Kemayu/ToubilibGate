<?php
declare(strict_types=1);

namespace toubilib\core\domain\entities\rdv;

final class RendezVous
{
    private string $id;
    private string $praticienId;
    private string $patientId;
    private ?string $patientEmail;
    private \DateTimeImmutable $debut;
    private ?\DateTimeImmutable $fin;
    private int $duree;
    private int $status; // 0 = scheduled, 1 = confirmed, 2 = cancelled
    private ?string $motif;

    public function __construct(array $data)
    {
        $this->id = (string)($data['id'] ?? '');
        $this->praticienId = (string)($data['praticien_id'] ?? '');
        $this->patientId = (string)($data['patient_id'] ?? '');
        $this->patientEmail = isset($data['patient_email']) ? (string)$data['patient_email'] : null;
        $this->debut = isset($data['date_heure_debut']) ? new \DateTimeImmutable($data['date_heure_debut']) : new \DateTimeImmutable();
        $this->fin = isset($data['date_heure_fin']) ? new \DateTimeImmutable($data['date_heure_fin']) : null;
        $this->duree = isset($data['duree']) ? (int)$data['duree'] : 0;
        $this->status = isset($data['status']) ? (int)$data['status'] : 0;
        $this->motif = isset($data['motif_visite']) ? (string)$data['motif_visite'] : null;
    }

    public function getId(): string { return $this->id; }
    public function getStatus(): int { return $this->status; }

    /**
     * Annule le rendez-vous si possible.
     * Lance RuntimeException en cas d'erreur métier.
     * @throws \RuntimeException
     */
    public function annuler(): void
    {
        if ($this->status === 1) {
            throw new \RuntimeException('rdv_already_cancelled');
        }
        $now = new \DateTimeImmutable();
        if ($this->debut <= $now) {
            throw new \RuntimeException('rdv_in_past');
        }
        $this->status = 1;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'praticien_id' => $this->praticienId,
            'patient_id' => $this->patientId,
            'patient_email' => $this->patientEmail,
            'date_heure_debut' => $this->debut->format('Y-m-d H:i:s'),
            'date_heure_fin' => $this->fin ? $this->fin->format('Y-m-d H:i:s') : null,
            'duree' => $this->duree,
            'status' => $this->status,
            'motif_visite' => $this->motif,
        ];
    }
}
