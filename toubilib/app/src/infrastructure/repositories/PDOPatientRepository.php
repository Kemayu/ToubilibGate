<?php

declare(strict_types=1);

namespace toubilib\infra\repositories;

use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;

class PDOPatientRepository implements PatientRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function getAllPatients(): array
    {
        $sql = 'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone FROM patient ORDER BY nom, prenom';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    public function getPatientById(string $id): ?array
    {
        $sql = 'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone FROM patient WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}