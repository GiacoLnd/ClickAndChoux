<?php
// src/Service/NewsletterService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NewsletterService
{
    private $httpClient;
    private $apiToken;
    
    public function __construct(HttpClientInterface $httpClient, string $apiToken)
    {
        $this->httpClient = $httpClient;
        $this->apiToken = $apiToken;
    }
    
    public function subscribeToNewsletter(string $email): bool
    {
        try {
            $response = $this->httpClient->request('POST', 'https://connect.mailerlite.com/api/subscribers', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $email,
                    'groups' => ['150373815016228781']
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            return $statusCode >= 200 && $statusCode < 300;
        } catch (\Exception $e) {
            return false;
        }
    }
}