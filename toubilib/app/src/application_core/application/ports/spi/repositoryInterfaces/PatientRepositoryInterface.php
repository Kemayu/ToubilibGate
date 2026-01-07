<?php

declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

interface PatientRepositoryInterface
{
    /**
     * Retourne la liste des patients 
     *
     * @return array[] 
     */
    public function getAllPatients(): array;

    /**
     * Retourne un patient par son id ou null si introuvable
     *
     * @param string $id
     * @return array|null
     */
    public function getPatientById(string $id): ?array;
}