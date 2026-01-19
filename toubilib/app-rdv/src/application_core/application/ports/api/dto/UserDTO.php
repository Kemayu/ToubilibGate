<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

/**
 * DTO (sans password)
 */
final class UserDTO
{
    private string $id;
    private string $email;
    private int $role;

    public function __construct(string $id, string $email, int $role)
    {
        $this->id = $id;
        $this->email = $email;
        $this->role = $role;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)$data['id'],
            (string)$data['email'],
            (int)$data['role']
        );
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

    /**
     * Retourne le libellé du rôle
     */
    private function getRoleLabel(): string
    {
        return match($this->role) {
            1 => 'patient',
            10 => 'praticien',
            100 => 'admin',
            default => 'unknown',
        };
    }
}