<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use toubilib\core\application\ports\api\dto\InputRendezVousDTO;

interface ServiceRendezVousInterface
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function listerCreneauxPraticien(string $praticienId, string $from, string $to): array;

    /**
     * @return array<string,mixed>|null
     */
    public function getRdvById(string $id): ?array;

    /**
     * @return array<string,mixed>
     */
    public function creerRendezVous(InputRendezVousDTO $dto): array;

    /**
     * @return array<string,mixed>|null
     */
    public function annulerRendezVous(string $id): ?array;

    /**
     * Met à jour le statut d'un rendez-vous
     * 
     * @param string $rdvId
     * @param int $newStatus 0=planifié, 1=annulé, 2=honoré, 3=non_honoré
     * @return array<string,mixed> ['success' => bool, 'code' => string, 'message' => string, 'rdv' => array|null]
     */
    public function updateRdvStatus(string $rdvId, int $newStatus): array;

    /**
     * Récupère l'historique des consultations d'un patient
     * 
     * @param string $patientId
     * @return array<int,\toubilib\core\application\ports\api\dto\RendezVousHistoriqueDTO>
     */
    public function getHistoriqueConsultations(string $patientId): array;
}
