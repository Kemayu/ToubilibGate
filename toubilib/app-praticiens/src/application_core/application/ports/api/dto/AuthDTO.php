<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\dto;

class AuthDTO
{
    public ProfileDTO $profile;
    public string $accessToken;
    public string $refreshToken;

    public function __construct(ProfileDTO $profile, string $accessToken, string $refreshToken)
    {
        $this->profile = $profile;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function toArray(): array
    {
        return [
            'profile' => $this->profile->toArray(),
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
        ];
    }
}
