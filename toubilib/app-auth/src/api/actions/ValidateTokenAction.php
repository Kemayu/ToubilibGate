<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\provider\AuthProviderExpiredAccessTokenException;
use toubilib\core\application\ports\api\provider\AuthProviderInvalidAccessTokenException;

class ValidateTokenAction extends AbstractAction
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $data = $request->getParsedBody();
        $token = $data['token'] ?? null;

        if (!$token) {
            $response->getBody()->write(json_encode(['error' => 'Token missing']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $profile = $this->authProvider->getSignedInUser($token);
            
            // Token valide, on retourne le profil
            $response->getBody()->write(json_encode([
                'valid' => true,
                'user' => $profile->toArray()
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (AuthProviderExpiredAccessTokenException $e) {
            $response->getBody()->write(json_encode(['error' => 'Token expired']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        } catch (AuthProviderInvalidAccessTokenException $e) {
            $response->getBody()->write(json_encode(['error' => 'Token invalid']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
}
