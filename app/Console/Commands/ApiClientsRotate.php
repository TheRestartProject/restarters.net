<?php

namespace App\Console\Commands;

use App\ApiClient;
use App\Services\ApiClientService;
use Illuminate\Console\Command;

class ApiClientsRotate extends Command
{
    protected $signature = 'api-clients:rotate {id : API client ID}';

    protected $description = 'Rotate an integration API client token';

    public function handle(ApiClientService $apiClientService): int
    {
        $client = ApiClient::find($this->argument('id'));

        if (! $client) {
            $this->error('API client not found.');
            return 1;
        }

        $plainToken = $apiClientService->rotate($client);

        $this->info("Rotated API client {$client->id} ({$client->name}).");
        $this->line("Token ID: {$client->token_hint}");
        $this->line("Token: {$plainToken}");
        $this->warn('Store this token now. It will not be shown again.');

        return 0;
    }
}
