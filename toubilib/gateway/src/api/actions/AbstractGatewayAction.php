<?php

namespace toubilib\gateway\api\actions;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractGatewayAction
{
    protected ClientInterface $remote_service;

    public function __construct(ClientInterface $client)
    {
        $this->remote_service = $client;
    }

    abstract public function __invoke(ServerRequestInterface $request,ResponseInterface $response,array $args): ResponseInterface;
}
