<?php
declare(strict_types=1);

namespace toubilib\infra\repositories;

use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\domain\entities\praticien\Praticien;

class PDOPraticienRepository implements PraticienRepositoryInterface
{


    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    public function getAllPraticien(): array
    {

        $stmt = $this->pdo->query('SELECT p.id, p.nom, p.prenom, p.ville, p.email, p.telephone, p.specialite_id, p.structure_id, p.rpps_id, p.organisation, p.nouveau_patient, p.titre FROM praticien p ORDER BY p.nom, p.prenom');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $praticiens = [];
        foreach ($results as $element) {
            $praticiens[] = Praticien::fromArray($element);
        }
        return $praticiens;
    }

    public function getPraticienById(string $id): ?array
    {
        $sql = '
            SELECT p.id, p.nom, p.prenom, p.ville, p.email, p.telephone, p.specialite_id, s.libelle AS specialite
            FROM praticien p
            LEFT JOIN specialite s ON p.specialite_id = s.id
            WHERE p.id = :id
            LIMIT 1
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        // motifs liés à la spécialité
        $motifs = [];
        if (!empty($row['specialite_id'])) {
            $stmt2 = $this->pdo->prepare('SELECT libelle FROM motif_visite WHERE specialite_id = :sid');
            $stmt2->execute(['sid' => $row['specialite_id']]);
            $motifs = array_column($stmt2->fetchAll(\PDO::FETCH_ASSOC), 'libelle');
        } else {
            $motifs = [];
        }

        // moyens de paiement (si table sans lien direct on renvoie tous)
        $stmt3 = $this->pdo->query('SELECT libelle FROM moyen_paiement');
        $moyens = array_column($stmt3->fetchAll(\PDO::FETCH_ASSOC), 'libelle');

        // adresse non présente dans la table praticien — garder null si absent
        return [
            'id' => $row['id'],
            'nom' => $row['nom'],
            'prenom' => $row['prenom'],
            'ville' => $row['ville'] ?? null,
            'email' => $row['email'] ?? null,
            'telephone' => $row['telephone'] ?? null,
            'specialite' => $row['specialite'] ?? null,
            'motifs' => $motifs,
            'moyens_paiement' => $moyens,
            'adresse' => null,
        ];
    }

    public function searchPraticiens(?int $specialiteId, ?string $ville): array
    {
        $sql = 'SELECT p.id, p.nom, p.prenom, p.ville, p.email, p.telephone, p.specialite_id, p.structure_id, p.rpps_id, p.organisation, p.nouveau_patient, p.titre FROM praticien p';
        $conditions = [];
        $params = [];

        if ($specialiteId !== null) {
            $conditions[] = 'p.specialite_id = :specialite_id';
            $params['specialite_id'] = $specialiteId;
        }

        if ($ville !== null) {
            $conditions[] = 'LOWER(p.ville) = LOWER(:ville)';
            $params['ville'] = $ville;
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY p.nom, p.prenom';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $praticiens = [];
        foreach ($results as $element) {
            $praticiens[] = Praticien::fromArray($element);
        }
        return $praticiens;
    }
}