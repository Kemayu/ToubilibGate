<?php
declare(strict_types=1);

namespace toubilib\core\application\services;

use toubilib\core\application\ports\api\dto\CredentialsDTO;
use toubilib\core\application\ports\api\dto\ProfileDTO;
use toubilib\core\application\ports\api\service\ToubilibAuthnServiceInterface;
use toubilib\core\application\ports\api\service\AuthenticationFailedException;
use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;

class ToubilibAuthnService implements ToubilibAuthnServiceInterface
{
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function byCredentials(CredentialsDTO $credentials): ProfileDTO
    {
        $userData = $this->authRepository->findUserByEmail($credentials->email);

        if ($userData === null) {
            throw new AuthenticationFailedException('Invalid credentials');
        }

        if (!password_verify($credentials->password, $userData['password'])) {
            throw new AuthenticationFailedException('Invalid credentials');
        }

        return new ProfileDTO(
            $userData['id'],  
            $userData['email'],
            (int)$userData['role']
        );
    }

    public function signup(CredentialsDTO $credentials, int $role): ProfileDTO
    {
        // Validation de l'email
        if (!filter_var($credentials->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        // Validation du mot de passe
        if (strlen($credentials->password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters');
        }

        // Hash du mot de passe
        $hashedPassword = password_hash($credentials->password, PASSWORD_BCRYPT);

        if ($hashedPassword === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        // Création de l'utilisateur via le repository
        $userId = $this->authRepository->createUser(
            $credentials->email,
            $hashedPassword,
            $role
        );

        // Retourne le profil de l'utilisateur créé
        return new ProfileDTO(
            $userId,
            $credentials->email,
            $role
        );
    }
}
