<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\service;

use toubilib\core\application\ports\api\dto\ProfileDTO;

/**
 * Interface du service d'autorisation
 * Définit les règles métier pour les contrôles d'accès
 */
interface AuthzServiceInterface
{
    /**
     * Vérifie si l'utilisateur peut accéder à l'agenda d'un praticien
     */
    public function canAccessAgenda(ProfileDTO $user, string $praticienId): bool;

    /**
     * Vérifie si l'utilisateur peut lire un rendez-vous
     */
    public function canAccessRdv(ProfileDTO $user, string $rdvId): bool;

    /**
     * Vérifie si l'utilisateur peut créer un rendez-vous
     */
    public function canCreateRdv(ProfileDTO $user): bool;

    /**
     * Vérifie si l'utilisateur peut modifier un rendez-vous
     */
    public function canUpdateRdv(ProfileDTO $user, string $rdvId): bool;

    /**
     * Vérifie si l'utilisateur peut supprimer un rendez-vous
     */
    public function canDeleteRdv(ProfileDTO $user, string $rdvId): bool;

    /**
     * Vérifie si l'utilisateur peut accéder à l'historique d'un patient
     */
    public function canAccessHistorique(ProfileDTO $user, string $patientId): bool;
}
