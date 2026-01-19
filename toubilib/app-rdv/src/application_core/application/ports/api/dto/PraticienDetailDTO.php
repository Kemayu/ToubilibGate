<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

final class PraticienDetailDTO
{
    private ?string $id;
    private string $nom;
    private string $prenom;
    private ?string $specialite;
    private ?string $email;
    private ?string $telephone;
    private ?string $ville;
    private ?string $adresse;
    /** @var string[] */
    private array $motifs;
    /** @var string[] */
    private array $moyensPaiement;

    public function __construct(
        ?string $id,
        string $nom,
        string $prenom,
        ?string $specialite = null,
        ?string $email = null,
        ?string $telephone = null,
        ?string $ville = null,
        ?string $adresse = null,
        array $motifs = [],
        array $moyensPaiement = []
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->specialite = $specialite;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->ville = $ville;
        $this->adresse = $adresse;
        $this->motifs = $motifs;
        $this->moyensPaiement = $moyensPaiement;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            (string)($data['nom'] ?? ''),
            (string)($data['prenom'] ?? ''),
            $data['specialite'] ?? null,
            $data['email'] ?? null,
            $data['telephone'] ?? null,
            $data['ville'] ?? null,
            $data['adresse'] ?? null,
            $data['motifs'] ?? [],
            $data['moyens_paiement'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'specialite' => $this->specialite,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'ville' => $this->ville,
            'adresse' => $this->adresse,
            'motifs' => $this->motifs,
            'moyens_paiement' => $this->moyensPaiement,
        ];
    }
}