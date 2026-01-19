<?php

declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use toubilib\core\application\ports\api\dto\PatientDTO;
use toubilib\core\application\ports\api\dto\PatientDetailDTO;

interface ServicePatientInterface
{
    /**
     * Retourne la liste des patients sous forme de tableaux/DTO.
     *
     * @return PatientDTO[] 
     */
    public function listerPatients(): array;

    /**
     * Retourne le détail d'un patient ou null si introuvable.
     *
     * @param string $id
     * @return PatientDetailDTO|null
     */
    public function getPatientDetail(string $id): ?PatientDetailDTO;
}