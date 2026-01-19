<?php

declare(strict_types=1);

namespace toubilib\core\application\usecases;

use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\application\ports\api\dto\PatientDTO;
use toubilib\core\application\ports\api\dto\PatientDetailDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePatientInterface;

class ServicePatient implements ServicePatientInterface
{
    private PatientRepositoryInterface $patientRepository;

    public function __construct(PatientRepositoryInterface $patientRepository)
    {
        $this->patientRepository = $patientRepository;
    }

    /**
     * @return PatientDTO[]
     */
    public function listerPatients(): array
    {
        $rows = $this->patientRepository->getAllPatients();
        $out = [];
        foreach ($rows as $r) {
            $out[] = PatientDTO::fromArray($r);
        }
        return $out;
    }

    public function getPatientDetail(string $id): ?PatientDetailDTO
    {
        $row = $this->patientRepository->getPatientById($id);
        if ($row === null) {
            return null;
        }
        return PatientDetailDTO::fromArray($row);
    }
}