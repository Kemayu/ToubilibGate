<?php
declare(strict_types=1);

namespace toubilib\core\domain\entities\praticien;

final class Praticien
{
    private ?string $id;
    private string $nom;
    private string $prenom;
    private ?string $ville;
    private ?string $email;
    private ?string $telephone;
    private ?int $specialiteId;
    private ?string $structureId;
    private ?string $rppsId;
    private bool $organisation;
    private bool $nouveauPatient;
    private ?string $titre;

    public function __construct(
        ?string $id,
        string $nom,
        string $prenom,
        ?string $ville = null,
        ?string $email = null,
        ?string $telephone = null,
        ?int $specialiteId = null,
        ?string $structureId = null,
        ?string $rppsId = null,
        bool $organisation = false,
        bool $nouveauPatient = true,
        ?string $titre = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->ville = $ville;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->specialiteId = $specialiteId;
        $this->structureId = $structureId;
        $this->rppsId = $rppsId;
        $this->organisation = $organisation;
        $this->nouveauPatient = $nouveauPatient;
        $this->titre = $titre;
    }

    public function getId(): ?string { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getVille(): ?string { return $this->ville; }
    public function getEmail(): ?string { return $this->email; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function getSpecialiteId(): ?int { return $this->specialiteId; }
    public function getStructureId(): ?string { return $this->structureId; }
    public function getRppsId(): ?string { return $this->rppsId; }
    public function isOrganisation(): bool { return $this->organisation; }
    public function isNouveauPatient(): bool { return $this->nouveauPatient; }
    public function getTitre(): ?string { return $this->titre; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'ville' => $this->ville,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'specialite_id' => $this->specialiteId,
            'structure_id' => $this->structureId,
            'rpps_id' => $this->rppsId,
            'organisation' => $this->organisation,
            'nouveau_patient' => $this->nouveauPatient,
            'titre' => $this->titre,
        ];
    }

    /**
     * Create entity from DB/array row.
     * Accepts common key variants (specialite_id, specialiteId, organisation as 0/1/'t'/'f').
     */
    public static function fromArray(array $data): Praticien
    {
        $getBool = function ($v): bool {
            if ($v === null) return false;
            if (is_bool($v)) return $v;
            if (is_int($v)) return $v !== 0;
            $s = (string)$v;
            return in_array(strtolower($s), ['1', 'true', 't', 'y', 'yes'], true);
        };

        $specialiteId = null;
        if (isset($data['specialite_id'])) {
            $specialiteId = is_numeric($data['specialite_id']) ? (int)$data['specialite_id'] : null;
        } elseif (isset($data['specialiteId'])) {
            $specialiteId = is_numeric($data['specialiteId']) ? (int)$data['specialiteId'] : null;
        }

        return new Praticien(
            $data['id'] ?? null,
            (string)($data['nom'] ?? ''),
            (string)($data['prenom'] ?? ''),
            $data['ville'] ?? null,
            $data['email'] ?? null,
            $data['telephone'] ?? null,
            $specialiteId,
            $data['structure_id'] ?? $data['structureId'] ?? null,
            $data['rpps_id'] ?? $data['rppsId'] ?? null,
            $getBool($data['organisation'] ?? $data['is_organisation'] ?? null),
            $getBool($data['nouveau_patient'] ?? $data['nouveauPatient'] ?? null),
            $data['titre'] ?? null
        );
    }
}