<?php
declare(strict_types=1);

namespace toubilib\core\application\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\provider\AuthProviderExpiredAccessTokenException;
use toubilib\core\application\ports\api\provider\AuthProviderInvalidAccessTokenException;
use Slim\Psr7\Response;

class AuthnMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            // Pas de token ? On laisse passer, l'AuthzMiddleware bloquera si besoin, ou on retourne 401.
            // Le TD dit "extraire et décoder", la gateway a déjà vérifié l'auth. 
            // Si pas de header, c'est louche venant de la gateway pour une route protégée.
            return $this->unauthorized('Missing Authorization header');
        }

        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorized('Invalid Authorization format');
        }

        $token = $matches[1];

        try {
            // Décodage du payload JWT sans vérification de signature (confiance Gateway)
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \Exception("Invalid token parts");
            }
            
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
            
            if (!$payload || !isset($payload['sub'], $payload['email'], $payload['role'])) {
                 throw new \Exception("Invalid token payload");
            }

            $profile = new \toubilib\core\application\ports\api\dto\ProfileDTO(
                $payload['sub'],
                $payload['email'],
                (int)$payload['role']
            );

            $request = $request->withAttribute('authenticated_user', $profile);

            return $handler->handle($request);
        } catch (\Exception $e) {
            return $this->unauthorized('Invalid token: ' . $e->getMessage());
        }
    }


    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
