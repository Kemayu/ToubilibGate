<?php
declare(strict_types=1);

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\provider\AuthProviderExpiredAccessTokenException;
use toubilib\core\application\ports\api\provider\AuthProviderInvalidAccessTokenException;

final class RefreshTokenAction
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array)$request->getParsedBody();
        $refreshToken = trim((string)($data['refresh_token'] ?? ''));

        if ($refreshToken === '') {
            $response->getBody()->write(json_encode(['error' => 'Refresh token is required']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $auth = $this->authProvider->refresh($refreshToken);

            $result = [
                'profile' => [
                    'id' => $auth->profile->ID,
                    'email' => $auth->profile->email,
                    'role' => $auth->profile->role,
                    'role_label' => $this->getRoleLabel($auth->profile->role),
                ],
                'access_token' => $auth->accessToken,
                'refresh_token' => $auth->refreshToken,
            ];

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (AuthProviderExpiredAccessTokenException $e) {
            $response->getBody()->write(json_encode(['error' => 'Refresh token expired']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');

        } catch (AuthProviderInvalidAccessTokenException $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid refresh token']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Token refresh failed']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    private function getRoleLabel(int $role): string
    {
        return match ($role) {
            1 => 'patient',
            10 => 'praticien',
            100 => 'admin',
            default => 'unknown',
        };
    }
}
