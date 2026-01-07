<?php
declare(strict_types=1);

namespace toubilib\core\application\ports\api\service;

use toubilib\core\application\ports\api\dto\CredentialsDTO;
use toubilib\core\application\ports\api\dto\ProfileDTO;

/**
 * Interface ToubilibAuthnServiceInterface
 * Service d'authentification métier
 */
interface ToubilibAuthnServiceInterface
{
    /**
     * Vérifie les credentials et retourne le profil utilisateur
     * @param CredentialsDTO $credentials Email et mot de passe
     * @return ProfileDTO Le profil si credentials valides
     * @throws AuthenticationFailedException Si credentials invalides
     */
    public function byCredentials(CredentialsDTO $credentials): ProfileDTO;

    /**
     * Inscrit un nouvel utilisateur
     * @param CredentialsDTO $credentials Email et mot de passe
     * @param int $role Le rôle de l'utilisateur
     * @return ProfileDTO Le profil créé
     */
    public function signup(CredentialsDTO $credentials, int $role): ProfileDTO;
}
