<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use toubilib\api\actions\AbstractAction;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dto\PraticienDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;


 class ListerPraticienAction extends AbstractAction
{
       protected ServicePraticienInterface $praticienService;

    public function __construct(ServicePraticienInterface $praticienService) {
        $this->praticienService = $praticienService;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface {
        // Récupérer les paramètres de requête
        $queryParams = $rq->getQueryParams();
        $specialiteId = isset($queryParams['specialite']) ? (int)$queryParams['specialite'] : null;
        $ville = isset($queryParams['ville']) ? trim($queryParams['ville']) : null;

        // Si au moins un critère est fourni, utiliser la recherche
        if ($specialiteId !== null || ($ville !== null && $ville !== '')) {
            $praticiens = $this->praticienService->rechercherPraticiens($specialiteId, $ville);
        } else {
            // Sinon, lister tous les praticiens
            $praticiens = $this->praticienService->listerPraticiens();
        }

        $praticiensArray = array_map(function(PraticienDTO $praticien) {
            return $praticien->toArray();
        }, $praticiens);

        $rs->getBody()->write(json_encode($praticiensArray));
        return $rs->withHeader('Content-Type', 'application/json');
    }
}