<?php

declare(strict_types=1);

namespace toubilib\api\actions;

use toubilib\core\application\ports\api\dto\CredentialsDTO;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\provider\AuthProviderInvalidCredentialsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use toubilib\api\actions\AbstractAction;

final class SignupAction extends AbstractAction
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
        if (!is_array($data)) {
            throw new HttpBadRequestException($request, 'Invalid payload');
        }

        $email = isset($data['email']) ? trim((string)$data['email']) : '';
        $password = isset($data['password']) ? (string)$data['password'] : '';
        $passwordConfirmation = isset($data['password_confirmation']) ? (string)$data['password_confirmation'] : null;

        if ($email === '' || $password === '') {
            throw new HttpBadRequestException($request, 'Email and password are required');
        }

        if ($passwordConfirmation !== null && $password !== $passwordConfirmation) {
            throw new HttpBadRequestException($request, 'Passwords do not match');
        }

        $credentials = new CredentialsDTO($email, $password);

        try {
            // Créer l'utilisateur avec le rôle patient (role = 1)
            $profile = $this->authProvider->signup($credentials, 1);
            
            // Connecter immédiatement pour fournir les tokens
            $authDTO = $this->authProvider->signin($credentials);

            $payload = [
                'success' => true,
                'message' => 'Account created successfully',
                'auth' => $authDTO->toArray(),
            ];

            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        } catch (\RuntimeException $e) {
            // duplicates or custom runtime errors -> 400
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Unexpected error during signup'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
