<?php
declare(strict_types=1);

namespace toubilib\core\application\usecases;

use toubilib\core\application\ports\api\dto\PraticienDetailDTO;
use toubilib\core\application\ports\api\dto\PraticienDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
class ServicePraticien implements ServicePraticienInterface
{
    private PraticienRepositoryInterface $praticienRepository;

    public function __construct(PraticienRepositoryInterface $praticienRepository)
    {
        $this->praticienRepository = $praticienRepository;
    }

    /**
     * @return PraticienDTO[] 
     */
    public function listerPraticiens(): array
    {       
        $praticiens = $this->praticienRepository->getAllPraticien();
        $praticienDTO = [];
        foreach($praticiens as $praticien){
            $praticienDTO[] = new PraticienDTO($praticien->getId(), $praticien->getNom(), $praticien->getPrenom(),$praticien->getVille(),$praticien->getEmail(),$praticien->getSpecialiteId());
        }
        return $praticienDTO; 
    }

     public function getPraticienDetail(string $id): ?PraticienDetailDTO
    {
        $data = $this->praticienRepository->getPraticienById($id);
        if ($data === null) {
            return null;
        }
        return PraticienDetailDTO::fromArray($data);
    }

    /**
     * @return PraticienDTO[]
     */
    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array
    {
        $praticiens = $this->praticienRepository->searchPraticiens($specialiteId, $ville);
        $praticienDTO = [];
        foreach($praticiens as $praticien){
            $praticienDTO[] = new PraticienDTO(
                $praticien->getId(), 
                $praticien->getNom(), 
                $praticien->getPrenom(),
                $praticien->getVille(),
                $praticien->getEmail(),
                $praticien->getSpecialiteId()
            );
        }
        return $praticienDTO;
    }
}