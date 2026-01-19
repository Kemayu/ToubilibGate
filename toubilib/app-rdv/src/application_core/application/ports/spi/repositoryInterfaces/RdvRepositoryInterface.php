<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

interface RdvRepositoryInterface
{
    public function findCreneauxPraticien(string $praticienId, string $from, string $to): array;

    public function findById(string $id): ?array;

    /**
     * Récupère un rendez-vous par son ID (alias de findById pour AuthzService)
     * 
     * @param string $id ID du rendez-vous
     * @return array|null Tableau avec praticien_id et patient_id
     */
    public function getRendezVousById(string $id): ?array;

    public function saveRendezVous(array $data): ?string;

    public function existsPraticienById(string $praticienId): bool;

    public function existsPatientById(string $patientId): bool;

    public function getMotifsForPraticien(string $praticienId): array;

    public function updateRendezVous(string $id, array $data): bool;

    /**
     * Met à jour le statut d'un rendez-vous
     * 
     * @param string $id ID du rendez-vous
     * @param int $status 0=planifié, 1=annulé, 2=honoré, 3=non_honoré
     * @return bool true si la mise à jour a réussi
     */
    public function updateStatus(string $id, int $status): bool;

    /**
     * Récupère l'historique des rendez-vous d'un patient
     * 
     * @param string $patientId ID du patient
     * @return array Tableau de rendez-vous avec informations praticien
     */
    public function getRendezVousByPatientId(string $patientId): array;

    /**
     * Vérifie si un praticien est indisponible sur une période donnée
     * 
     * @param string $praticienId ID du praticien
     * @param \DateTimeImmutable $debut Date/heure de début
     * @param \DateTimeImmutable $fin Date/heure de fin
     * @return bool true si le praticien est indisponible (en congé, férié, etc.)
     */
    public function isPraticienIndisponible(string $praticienId, \DateTimeImmutable $debut, \DateTimeImmutable $fin): bool;
}
