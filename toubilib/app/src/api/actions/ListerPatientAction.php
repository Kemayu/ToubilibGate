<?php

declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePatientInterface;

class ListerPatientAction extends AbstractAction
{
    private ServicePatientInterface $service;

    public function __construct(ServicePatientInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $dtos = $this->service->listerPatients();
        $out = [];
        foreach ($dtos as $d) {
            $out[] = $d->toArray();
        }
        
        $rs->getBody()->write(json_encode(['data' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $rs->withHeader('Content-Type', 'application/json');
    }
}