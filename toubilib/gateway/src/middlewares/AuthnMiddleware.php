<?php
declare(strict_types=1);

namespace toubilib\gateway\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Exception\HttpInternalServerErrorException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;

class AuthnMiddleware implements MiddlewareInterface
{
    private ClientInterface $authClient;

    public function __construct(ClientInterface $authClient)
    {
        $this->authClient = $authClient;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader('Authorization')) {
             throw new HttpUnauthorizedException($request, "Authorization token missing");
        }

        $tokenHeader = $request->getHeaderLine('Authorization');
        list($token) = sscanf($tokenHeader, "Bearer %s");

        if (!$token) {
            throw new HttpUnauthorizedException($request, "Malformed Authorization header");
        }

        try {
            $this->authClient->request('POST', '/tokens/validate', [
                'json' => ['token' => $token],
                'http_errors' => true
            ]);
        } catch (ConnectException | ServerException $e) {
            throw new HttpInternalServerErrorException($request, "Auth service unavailable");
        } catch (ClientException $e) {
            match($e->getCode()) {
                401 => throw new HttpUnauthorizedException($request, "unauthorized ({$e->getCode()}, {$e->getMessage()})"),
                default => throw new HttpInternalServerErrorException($request, "Auth error: " . $e->getMessage()),
            };
        }

        return $handler->handle($request);
    }
}
