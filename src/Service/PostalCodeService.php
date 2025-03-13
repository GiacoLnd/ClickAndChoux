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

    // TODO : API code postaux data.gouv.fr
    public function getPostalCodes(string $query)
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => [
                    'q' => $query, 
                    'type' => 'municipality', 
                ],
            ]);

            $data = $response->toArray();

            return $data['features'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
