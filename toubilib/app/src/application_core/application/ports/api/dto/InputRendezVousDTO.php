<?php

declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

final class InputRendezVousDTO
{
    private string $praticienId;
    private string $patientId;
    private string $dateHeureDebut; // ISO8601
    private string $motifVisite;
    private int $duree;

    public function __construct(string $praticienId, string $patientId, string $dateHeureDebut, string $motifVisite, int $duree)
    {
        $this->praticienId = $praticienId;
        $this->patientId = $patientId;
        $this->dateHeureDebut = $dateHeureDebut;
        $this->motifVisite = $motifVisite;
        $this->duree = $duree;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['praticien_id'] ?? $data['praticienId'] ?? ''),
            (string)($data['patient_id'] ?? $data['patientId'] ?? ''),
            (string)($data['date_heure_debut'] ?? $data['dateHeureDebut'] ?? ''),
            (string)($data['motif_visite'] ?? $data['motifVisite'] ?? ''),
            isset($data['duree']) ? (int)$data['duree'] : 30
        );
    }

    public function toArray(): array
    {
        return [
            'praticien_id' => $this->praticienId,
            'patient_id' => $this->patientId,
            'date_heure_debut' => $this->dateHeureDebut,
            'motif_visite' => $this->motifVisite,
            'duree' => $this->duree,
        ];
    }
}