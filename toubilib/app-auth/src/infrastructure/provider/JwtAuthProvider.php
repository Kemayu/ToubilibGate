<?php
declare(strict_types=1);

namespace toubilib\infra\provider;

use toubilib\core\application\ports\api\dto\CredentialsDTO;
use toubilib\core\application\ports\api\dto\AuthDTO;
use toubilib\core\application\ports\api\dto\ProfileDTO;
use toubilib\core\application\ports\api\provider\AuthProviderInterface;
use toubilib\core\application\ports\api\provider\AuthProviderInvalidCredentialsException;
use toubilib\core\application\ports\api\provider\AuthProviderExpiredAccessTokenException;
use toubilib\core\application\ports\api\provider\AuthProviderInvalidAccessTokenException;
use toubilib\core\application\ports\api\jwt\JwtManagerInterface;
use toubilib\core\application\ports\api\jwt\JwtManagerExpiredTokenException;
use toubilib\core\application\ports\api\jwt\JwtManagerInvalidTokenException;
use toubilib\core\application\ports\api\service\ToubilibAuthnServiceInterface;
use toubilib\core\application\ports\api\service\AuthenticationFailedException;

class JwtAuthProvider implements AuthProviderInterface
{
    private ToubilibAuthnServiceInterface $authnService;
    private JwtManagerInterface $jwtManager;

    public function __construct(
        ToubilibAuthnServiceInterface $authnService,
        JwtManagerInterface $jwtManager
    ) {
        $this->authnService = $authnService;
        $this->jwtManager = $jwtManager;
    }

    public function signup(CredentialsDTO $credentials, int $role): ProfileDTO
    {
        return $this->authnService->signup($credentials, $role);
    }

    public function signin(CredentialsDTO $credentials): AuthDTO
    {
        try {
            $profile = $this->authnService->byCredentials($credentials);

            $accessToken = $this->jwtManager->create($profile, JwtManagerInterface::ACCESS_TOKEN);
            $refreshToken = $this->jwtManager->create($profile, JwtManagerInterface::REFRESH_TOKEN);

            return new AuthDTO($profile, $accessToken, $refreshToken);
        } catch (AuthenticationFailedException $e) {
            throw new AuthProviderInvalidCredentialsException('Invalid credentials', 0, $e);
        }
    }

    public function getSignedInUser(string $accessToken): ProfileDTO
    {
        try {
            return $this->jwtManager->validate($accessToken);
        } catch (JwtManagerExpiredTokenException $e) {
            throw new AuthProviderExpiredAccessTokenException('Access token expired', 0, $e);
        } catch (JwtManagerInvalidTokenException $e) {
            throw new AuthProviderInvalidAccessTokenException('Invalid access token', 0, $e);
        }
    }

    public function refresh(string $refreshToken): AuthDTO
    {
        try {
            // Valider le refresh token et récupérer le profil
            $profile = $this->jwtManager->validate($refreshToken);

            // Générer de nouveaux tokens
            $newAccessToken = $this->jwtManager->create($profile, JwtManagerInterface::ACCESS_TOKEN);
            $newRefreshToken = $this->jwtManager->create($profile, JwtManagerInterface::REFRESH_TOKEN);

            return new AuthDTO($profile, $newAccessToken, $newRefreshToken);
        } catch (JwtManagerExpiredTokenException $e) {
            throw new AuthProviderExpiredAccessTokenException('Refresh token expired', 0, $e);
        } catch (JwtManagerInvalidTokenException $e) {
            throw new AuthProviderInvalidAccessTokenException('Invalid refresh token', 0, $e);
        }
    }
}
