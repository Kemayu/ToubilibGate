<?php

declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

final class RendezVousHistoriqueDTO
{
    private string $id;
    private string $dateHeureDebut;
    private ?string $dateHeureFin;
    private int $duree;
    private int $status;
    private string $statusLabel;
    private ?string $motifVisite;
    private ?string $dateCreation;
    private string $praticienId;
    private string $praticienNom;
    private string $praticienPrenom;
    private ?string $praticienSpecialite;

    public function __construct(
        string $id,
        string $dateHeureDebut,
        ?string $dateHeureFin,
        int $duree,
        int $status,
        ?string $motifVisite,
        ?string $dateCreation,
        string $praticienId,
        string $praticienNom,
        string $praticienPrenom,
        ?string $praticienSpecialite
    ) {
        $this->id = $id;
        $this->dateHeureDebut = $dateHeureDebut;
        $this->dateHeureFin = $dateHeureFin;
        $this->duree = $duree;
        $this->status = $status;
        $this->statusLabel = $this->getStatusLabel($status);
        $this->motifVisite = $motifVisite;
        $this->dateCreation = $dateCreation;
        $this->praticienId = $praticienId;
        $this->praticienNom = $praticienNom;
        $this->praticienPrenom = $praticienPrenom;
        $this->praticienSpecialite = $praticienSpecialite;
    }

    private function getStatusLabel(int $status): string
    {
        return match($status) {
            0 => 'planifié',
            1 => 'annulé',
            2 => 'honoré',
            3 => 'non_honoré',
            default => 'inconnu'
        };
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)$data['id'],
            (string)$data['date_heure_debut'],
            $data['date_heure_fin'] ?? null,
            (int)($data['duree'] ?? 30),
            (int)($data['status'] ?? 0),
            $data['motif_visite'] ?? null,
            $data['date_creation'] ?? null,
            (string)$data['praticien_id'],
            (string)$data['praticien_nom'],
            (string)$data['praticien_prenom'],
            $data['praticien_specialite'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date_heure_debut' => $this->dateHeureDebut,
            'date_heure_fin' => $this->dateHeureFin,
            'duree' => $this->duree,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'motif_visite' => $this->motifVisite,
            'date_creation' => $this->dateCreation,
            'praticien' => [
                'id' => $this->praticienId,
                'nom' => $this->praticienNom,
                'prenom' => $this->praticienPrenom,
                'specialite' => $this->praticienSpecialite,
            ],
        ];
    }
}
