<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePatientInterface;

class PatientDetailAction extends AbstractAction
{
    private ServicePatientInterface $service;

    public function __construct(ServicePatientInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $id = (string)($args['id'] ?? '');
        $dto = $this->service->getPatientDetail($id);
        
        if ($dto === null) {
            $rs->getBody()->write(json_encode(['message' => 'Patient not found'], JSON_UNESCAPED_UNICODE));
            return $rs->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $rs->getBody()->write(json_encode($dto->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $rs->withHeader('Content-Type', 'application/json');
    }
}