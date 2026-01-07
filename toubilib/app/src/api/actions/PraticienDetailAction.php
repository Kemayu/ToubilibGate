<?php

declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;

class PraticienDetailAction extends AbstractAction
{
    protected ServicePraticienInterface $praticienService;

    public function __construct(ServicePraticienInterface $praticienService)
    {
        $this->praticienService = $praticienService;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $id = (string)($args['id'] ?? '');
        $dto = $this->praticienService->getPraticienDetail($id);
        if ($dto === null) {
            $rs->getBody()->write(json_encode(['message' => 'Praticien not found']));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        $rs->getBody()->write(json_encode($dto->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $rs->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}