<?php

declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

final class PatientDetailDTO
{
    private ?string $id;
    private string $nom;
    private string $prenom;
    private ?string $dateNaissance;
    private ?string $adresse;
    private ?string $codePostal;
    private ?string $ville;
    private ?string $email;
    private ?string $telephone;

    public function __construct(
        ?string $id,
        string $nom,
        string $prenom,
        ?string $dateNaissance = null,
        ?string $adresse = null,
        ?string $codePostal = null,
        ?string $ville = null,
        ?string $email = null,
        ?string $telephone = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->dateNaissance = $dateNaissance;
        $this->adresse = $adresse;
        $this->codePostal = $codePostal;
        $this->ville = $ville;
        $this->email = $email;
        $this->telephone = $telephone;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? null,
            (string)($data['nom'] ?? ''),
            (string)($data['prenom'] ?? ''),
            $data['date_naissance'] ?? null,
            $data['adresse'] ?? null,
            $data['code_postal'] ?? null,
            $data['ville'] ?? null,
            $data['email'] ?? null,
            $data['telephone'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'date_naissance' => $this->dateNaissance,
            'adresse' => $this->adresse,
            'code_postal' => $this->codePostal,
            'ville' => $this->ville,
            'email' => $this->email,
            'telephone' => $this->telephone,
        ];
    }
}