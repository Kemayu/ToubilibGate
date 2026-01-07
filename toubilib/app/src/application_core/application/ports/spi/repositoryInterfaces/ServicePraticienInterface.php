<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use toubilib\core\application\ports\api\dto\PraticienDetailDTO;
use toubilib\core\domain\entities\praticien\Praticien;

interface ServicePraticienInterface
{
    /**
     * Retourne la liste complète des praticiens (sans pagination / filtres).
     *
     * @return Praticien[]
     */
    public function listerPraticiens(): array;

    public function getPraticienDetail(string $id): ?PraticienDetailDTO;

    /**
     * Recherche des praticiens selon des critères optionnels
     *
     * @param int|null $specialiteId Identifiant de la spécialité
     * @param string|null $ville Ville d'exercice
     * @return array Tableau de PraticienDTO
     */
    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array;
}
