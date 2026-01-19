<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

use toubilib\core\domain\entities\praticien\Praticien;

final class PraticienDTO
{
    private ?string $id;
    private string $nom;
    private string $prenom;
    private ?string $ville;
    private ?string $email;
    private ?int $specialite;

    public function __construct(
        ?string $id,
        string $nom,
        string $prenom,
        ?string $ville = null,
        ?string $email = null,
        ?int $specialite = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->ville = $ville;
        $this->email = $email;
        $this->specialite = $specialite;
    }

    public static function fromEntity(Praticien $p): self
    {
        return new self(
            $p->getId(),
            $p->getNom(),
            $p->getPrenom(),
            $p->getVille(),
            $p->getEmail(),
            $p->getSpecialiteId()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'ville' => $this->ville,
            'email' => $this->email,
            'specialite_id' => $this->specialite,
        ];
    }


}