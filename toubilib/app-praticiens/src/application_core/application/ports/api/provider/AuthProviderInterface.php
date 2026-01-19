<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\provider;

use toubilib\core\application\ports\api\dto\CredentialsDTO;
use toubilib\core\application\ports\api\dto\AuthDTO;
use toubilib\core\application\ports\api\dto\ProfileDTO;

/**
 * Interface AuthProviderInterface
 * Définit les méthodes pour l'authentification JWT
 */
interface AuthProviderInterface
{
    /**
     * Enregistre un nouvel utilisateur
     * @param CredentialsDTO $credentials Email et mot de passe
     * @param int $role Le rôle de l'utilisateur (1=patient, 10=praticien, 100=admin)
     * @return ProfileDTO Le profil créé
     */
    public function signup(CredentialsDTO $credentials, int $role): ProfileDTO;

    /**
     * Authentifie un utilisateur avec ses credentials
     * @param CredentialsDTO $credentials Email et mot de passe
     * @return AuthDTO Le profil + les tokens JWT
     * @throws AuthProviderInvalidCredentialsException Si credentials invalides
     */
    public function signin(CredentialsDTO $credentials): AuthDTO;

    /**
     * Récupère le profil utilisateur depuis un access token
     * @param string $accessToken Le token JWT
     * @return ProfileDTO Le profil de l'utilisateur authentifié
     * @throws AuthProviderExpiredAccessTokenException Si le token a expiré
     * @throws AuthProviderInvalidAccessTokenException Si le token est invalide
     */
    public function getSignedInUser(string $accessToken): ProfileDTO;

    /**
     * Régénère un access token à partir d'un refresh token valide
     * @param string $refreshToken Le refresh token JWT
     * @return AuthDTO Le profil + nouveaux tokens JWT
     * @throws AuthProviderExpiredAccessTokenException Si le refresh token a expiré
     * @throws AuthProviderInvalidAccessTokenException Si le refresh token est invalide
     */
    public function refresh(string $refreshToken): AuthDTO;
}
