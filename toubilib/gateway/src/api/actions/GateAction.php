<?php
declare(strict_types=1);

namespace toubilib\gateway\api\actions;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Container\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpInternalServerErrorException;

class GateAction
{
    private ClientInterface $defaultClient;
    private ContainerInterface $container;

    public function __construct(ClientInterface $client, ContainerInterface $container)
    {
        $this->defaultClient = $client;
        $this->container = $container;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        try {
            // Déterminer le service cible basé sur la route
            $client = $this->getClientForRoute($request->getUri()->getPath());
            
            // Récupérer le URI de la requête
            $uri = $request->getUri()->getPath();
            $method = $request->getMethod();
            
            // Déterminer le service cible basé sur la route
            $targetUri = $this->buildTargetUri($uri, $args);
            
            // Construire les options pour Guzzle
            $options = [
                'query' => $request->getQueryParams(),
                'headers' => $this->forwardHeaders($request),
            ];
            
            // Si le body n'est pas vide, l'ajouter
            $bodyContent = (string) $request->getBody();
            if (!empty($bodyContent)) {
                $options['body'] = $bodyContent;
            }
            
            // Effectuer la requête au service cible
            $remoteResponse = $client->request($method, $targetUri, $options);
            
            // Copier le body et les headers de la réponse
            $body = $remoteResponse->getBody();
            $response->getBody()->write((string)$body);
            
            // Copier les headers pertinents
            foreach ($remoteResponse->getHeaders() as $name => $values) {
                if (!in_array(strtolower((String)$name), ['connection', 'content-length', 'transfer-encoding'])) {
                    $response = $response->withHeader((String)$name, $values);
                }
            }
            
            return $response->withStatus($remoteResponse->getStatusCode());
            
        } catch (ClientException $e) {
            // Gestion des erreurs HTTP (4xx et 5xx)
            $statusCode = $e->getResponse()->getStatusCode();
            
            // Pour TOUS les erreurs client, on renvoie la réponse du service distant
            // (y compris les 404, 400, 403, etc.)
            return $e->getResponse();
            
        } catch (RequestException $e) {
            // Si une réponse est disponible (ex: 500 du serveur distant), on la renvoie telle quelle
            if ($e->hasResponse()) {
                 return $e->getResponse();
            }
            
            // Sinon, c'est une erreur de connexion ou autre
            throw new HttpInternalServerErrorException(
                $request,
                "Erreur de communication avec le service distant: " . $e->getMessage()
            );
        }
    }

    /**
     * Déterminer le client à utiliser basé sur la route
     */
    private function getClientForRoute(string $path): ClientInterface
    {
        // Exception: /praticiens/{id}/creneaux est géré par le service RDV
        if (strpos($path, '/creneaux') !== false) {
             if ($this->container->has('client.rdv')) {
                return $this->container->get('client.rdv');
            }
        }

        // Routes pour le service praticiens
        if (strpos($path, '/praticiens') === 0) {
            // Retourner le client praticiens si disponible, sinon le client par défaut
            if ($this->container->has('client.praticiens')) {
                return $this->container->get('client.praticiens');
            }
        }
        
        // Routes pour le service RDV et patients
        if (
            strpos($path, '/rdvs') === 0 ||
            strpos($path, '/patients') === 0
        ) {
            // Retourner le client RDV si disponible, sinon le client par défaut
            if ($this->container->has('client.rdv')) {
                return $this->container->get('client.rdv');
            }
        }
        
        // Retourner le client par défaut auth
        return $this->defaultClient;
    }

    /**
     * Construire l'URI cible basé sur la route
     */
    private function buildTargetUri(string $uri, array $args): string
    {
        // Remplacer les paramètres de route dans l'URI
        $targetUri = $uri;
        foreach ($args as $key => $value) {
            $targetUri = str_replace('{' . $key . '}', (string)$value, $targetUri);
        }
        
        return $targetUri;
    }

    /**
     * Transférer les headers pertinents de la requête
     */
    private function forwardHeaders(ServerRequestInterface $request): array
    {
        $allowedHeaders = [
            'Authorization',
            'Content-Type',
            'Accept',
            'Accept-Language',
            'User-Agent',
            'X-Requested-With',
        ];
        
        $headers = [];
        foreach ($allowedHeaders as $header) {
            if ($request->hasHeader($header)) {
                $headers[$header] = $request->getHeaderLine($header);
            }
        }
        
        return $headers;
    }
}

