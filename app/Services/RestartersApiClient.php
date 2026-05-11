<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class RestartersApiClient
{
    public function __construct(
        protected string $apiToken,
        protected ?string $baseUrl = null,
    ) {
        $this->baseUrl = rtrim(
            $baseUrl ?: config('services.restarters.base_url', 'https://restarters.net/api/v2'),
            '/'
        );
    }

    public function get(string $endpoint): ?array
    {
        return $this->request('GET', $endpoint);
    }

    public function post(string $endpoint, array $payload): ?array
    {
        return $this->request('POST', $endpoint, $payload);
    }

    protected function request(string $method, string $endpoint, ?array $payload = null, int $retries = 3): ?array
    {
        $url = $this->baseUrl . $endpoint;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $request = Http::acceptJson()
                    ->withQueryParameters([
                        'api_token' => $this->apiToken,
                    ]);

                $response = match (strtoupper($method)) {
                    'GET' => $request->get($url),
                    'POST' => $request->post($url, $payload ?? []),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method [{$method}]"),
                };

                $response->throw();

                return $response->json();
            } catch (\Throwable $e) {
                if ($attempt === $retries) {
                    throw $e;
                }
            }
        }

        return null;
    }
}
