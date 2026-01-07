<?php
declare(strict_types=1);

namespace toubilib\core\domain\entities\auth;

final class User
{
    private string $id;
    private string $email;
    private string $password;
    private int $role;

    public function __construct(
        string $id,
        string $email,
        string $password,
        int $role
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRole(): int
    {
        return $this->role;
    }

  
    public function getRoleLabel(): string
    {
        return match($this->role) {
            1 => 'patient',
            10 => 'praticien',
            100 => 'admin',
            default => 'unknown',
        };
    }

    public function isPatient(): bool
    {
        return $this->role === 1;
    }

    public function isPraticien(): bool
    {
        return $this->role === 10;
    }


    public function isAdmin(): bool
    {
        return $this->role === 100;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'role_label' => $this->getRoleLabel(),
        ];
    }

    public static function fromArray(array $data): User
    {
        return new User(
            (string)$data['id'],
            (string)$data['email'],
            (string)$data['password'],
            (int)$data['role']
        );
    }
}