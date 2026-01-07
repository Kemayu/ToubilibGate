<?php
declare(strict_types=1);

namespace toubilib\infra\repositories;

use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;

class PDOAuthRepository implements AuthRepositoryInterface
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findUserByEmail(string $email): ?array
    {
        $sql = 'SELECT id, email, password, role FROM users WHERE email = :email LIMIT 1';
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row) {
                return null;
            }

            return [
                'id' => (string)$row['id'],
                'email' => (string)$row['email'],
                'password' => (string)$row['password'],
                'role' => (int)$row['role'],
            ];
        } catch (\Throwable $e) {
            error_log('PDOAuthRepository error: ' . $e->getMessage());
            return null;
        }
    }

    public function createUser(string $email, string $hashedPassword, int $role): string
    {
        // Vérifier si l'email existe déjà
        if ($this->findUserByEmail($email) !== null) {
            throw new \RuntimeException('Email already exists');
        }

        // Générer un ID unique
        $userId = bin2hex(random_bytes(16));

        $sql = 'INSERT INTO users (id, email, password, role) VALUES (:id, :email, :password, :role)';
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $userId,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
            ]);

            return $userId;
        } catch (\PDOException $e) {
            // Gestion des erreurs de contraintes (duplicate key)
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('Email already exists');
            }
            throw new \RuntimeException('Failed to create user: ' . $e->getMessage());
        }
    }
}