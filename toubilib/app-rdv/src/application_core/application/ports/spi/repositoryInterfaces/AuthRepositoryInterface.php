<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

interface AuthRepositoryInterface
{
    /**
     * Récupère un utilisateur par email
     * 
     * @param string $email
     * @return array|null 
     */
    public function findUserByEmail(string $email): ?array;

    /**
     * Crée un nouvel utilisateur
     * 
     * @param string $email
     * @param string $hashedPassword
     * @param int $role
     * @return string L'ID de l'utilisateur créé
     * @throws \RuntimeException Si l'email existe déjà
     */
    public function createUser(string $email, string $hashedPassword, int $role): string;
}