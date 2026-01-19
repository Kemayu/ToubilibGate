<?php
declare(strict_types=1);

namespace toubilib\core\application\usecases;

use toubilib\core\application\ports\api\dto\UserDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;

class ServiceAuth
{
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

 
    public function authenticate(string $email, string $password): ?UserDTO
    {
        if (empty($email) || empty($password)) {
            return null;
        }

        $user = $this->authRepository->findUserByEmail($email);

        if ($user === null) {
            return null;
        }

        // Verif (hash bcrypt)
        if (!password_verify($password, $user['password'])) {
            return null; 
        }

    
        return new UserDTO(
            $user['id'],
            $user['email'],
            (int)$user['role']
        );
    }
}