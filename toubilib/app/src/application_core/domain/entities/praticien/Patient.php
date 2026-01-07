<?php

declare(strict_types=1);

namespace toubilib\core\domain\entities\patient;

final class Patient
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

    public function getId(): ?string { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function getPrenom(): string { return $this->prenom; }
    public function getDateNaissance(): ?string { return $this->dateNaissance; }
    public function getAdresse(): ?string { return $this->adresse; }
    public function getCodePostal(): ?string { return $this->codePostal; }
    public function getVille(): ?string { return $this->ville; }
    public function getEmail(): ?string { return $this->email; }
    public function getTelephone(): ?string { return $this->telephone; }

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

    public static function fromArray(array $data): Patient
    {
        return new Patient(
            $data['id'] ?? null,
            (string)($data['nom'] ?? ''),
            (string)($data['prenom'] ?? ''),
            $data['date_naissance'] ?? $data['dateNaissance'] ?? null,
            $data['adresse'] ?? null,
            $data['code_postal'] ?? $data['codePostal'] ?? null,
            $data['ville'] ?? null,
            $data['email'] ?? null,
            $data['telephone'] ?? null
        );
    }
}