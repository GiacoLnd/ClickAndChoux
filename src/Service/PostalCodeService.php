<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PostalCodeService
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getPostalCodes(string $query)
    {
        try {
            // Appel à l'API des codes postaux de data.gouv.fr
            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => [
                    'q' => $query, // La ville, commune, ou code postal
                    'type' => 'municipality', // On peut aussi utiliser 'postcode' selon le besoin
                ],
            ]);

            // Récupérer les données en tant que tableau
            $data = $response->toArray();

            return $data['features']; // Retourne les résultats des communes
        } catch (\Exception $e) {
            // Gérer les erreurs
            return ['error' => $e->getMessage()];
        }
    }
}
