<?php
declare(strict_types=1);

namespace toubilib\core\application\services;

use toubilib\core\application\ports\api\dto\ProfileDTO;
use toubilib\core\application\ports\api\service\AuthzServiceInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;

class AuthzService implements AuthzServiceInterface
{
    private const ROLE_PATIENT = 1;
    private const ROLE_PRATICIEN = 10;
    private const ROLE_ADMIN = 100;

    private RdvRepositoryInterface $rdvRepository;

    public function __construct(RdvRepositoryInterface $rdvRepository)
    {
        $this->rdvRepository = $rdvRepository;
    }

    public function canAccessAgenda(ProfileDTO $user, string $praticienId): bool
    {
        // Opération 7: uniquement praticien authentifié propriétaire de l'agenda
        if ($user->role === self::ROLE_PRATICIEN && $user->ID === $praticienId) {
            return true;
        }

        return false;
    }

    public function canAccessRdv(ProfileDTO $user, string $rdvId): bool
    {
        // Opération 4,6: praticien du RDV = praticien authentifié OU patient du RDV = patient authentifié
        $rdv = $this->rdvRepository->getRendezVousById($rdvId);
        
        if ($rdv === null) {
            return false;
        }

        // Praticien du RDV vérifie son propre RDV
        if ($user->role === self::ROLE_PRATICIEN && $rdv['praticien_id'] === $user->ID) {
            return true;
        }

        // Patient du RDV vérifie son propre RDV
        if ($user->role === self::ROLE_PATIENT && $rdv['patient_id'] === $user->ID) {
            return true;
        }

        return false;
    }

    public function canCreateRdv(ProfileDTO $user): bool
    {
        // Opération 6: uniquement patient authentifié
        return $user->role === self::ROLE_PATIENT;
    }

    public function canUpdateRdv(ProfileDTO $user, string $rdvId): bool
    {
        // Opération 10: uniquement praticien authentifié = praticien du RDV
        if ($user->role !== self::ROLE_PRATICIEN) {
            return false;
        }

        $rdv = $this->rdvRepository->getRendezVousById($rdvId);
        
        if ($rdv === null) {
            return false;
        }

        return $rdv['praticien_id'] === $user->ID;
    }

    public function canDeleteRdv(ProfileDTO $user, string $rdvId): bool
    {
        // Opération 5: uniquement patient ET patient RDV = patient authentifié
        if ($user->role !== self::ROLE_PATIENT) {
            return false;
        }

        $rdv = $this->rdvRepository->getRendezVousById($rdvId);
        
        if ($rdv === null) {
            return false;
        }

        return $rdv['patient_id'] === $user->ID;
    }

    public function canAccessHistorique(ProfileDTO $user, string $patientId): bool
    {
        // Opération 11: patient authentifié = patient pour lequel l'historique est demandé
        if ($user->role !== self::ROLE_PATIENT) {
            return false;
        }

        return $user->ID === $patientId;
    }
}
