<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;;

interface PraticienRepositoryInterface
{
    /**
     * Retourne la liste complète des praticiens
     *
     * @return array
     */

    public function getAllPraticien(): array;

    public function getPraticienById(string $id): ?array;

    /**
     * Recherche des praticiens selon des critères optionnels
     *
     * @param int|null $specialiteId Identifiant de la spécialité
     * @param string|null $ville Ville d'exercice
     * @return array Tableau d'entités Praticien
     */
    public function searchPraticiens(?int $specialiteId, ?string $ville): array;
}