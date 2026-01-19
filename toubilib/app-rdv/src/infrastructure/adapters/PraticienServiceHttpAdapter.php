<?php
declare(strict_types=1);

namespace toubilib\infrastructure\adapters;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use toubilib\core\application\ports\spi\repositoryInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\api\dto\PraticienDetailDTO;
use toubilib\core\domain\entities\praticien\Praticien;


//   Adaptateur HTTP pour accéder au service praticiens via l'API

class PraticienServiceHttpAdapter implements ServicePraticienInterface
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function listerPraticiens(): array
    {
        try {
            $response = $this->client->request('GET', '/praticiens');
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!is_array($data)) {
                return [];
            }

            // Convertir les données HTTP en entités Praticien
            return array_map(function ($praticienData) {
                return Praticien::fromArray($praticienData);
            }, $data);
        } catch (GuzzleException $e) {
            // En cas d'erreur de communication, retourner liste vide
            error_log("Erreur lors de la récupération des praticiens: " . $e->getMessage());
            return [];
        }
    }

    public function getPraticienDetail(string $id): ?PraticienDetailDTO
    {
        try {
            $response = $this->client->request('GET', "/praticiens/{$id}");
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!is_array($data) || empty($data)) {
                return null;
            }

            // Utiliser la méthode fromArray du DTO pour construire l'objet
            return PraticienDetailDTO::fromArray($data);
        } catch (GuzzleException $e) {
            error_log("Erreur lors de la récupération du praticien {$id}: " . $e->getMessage());
            return null;
        }
    }

    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array
    {
        try {
            $queryParams = [];
            
            if ($specialiteId !== null) {
                $queryParams['specialite_id'] = $specialiteId;
            }
            
            if ($ville !== null) {
                $queryParams['ville'] = $ville;
            }

            $response = $this->client->request('GET', '/praticiens', [
                'query' => $queryParams
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!is_array($data)) {
                return [];
            }

            return array_map(function ($praticienData) {
                return Praticien::fromArray($praticienData);
            }, $data);
        } catch (GuzzleException $e) {
            error_log("Erreur lors de la recherche de praticiens: " . $e->getMessage());
            return [];
        }
    }

}
