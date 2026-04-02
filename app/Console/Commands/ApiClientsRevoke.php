<?php

namespace App\Console\Commands;

use App\ApiClient;
use Illuminate\Console\Command;

class ApiClientsRevoke extends Command
{
    protected $signature = 'api-clients:revoke {id : API client ID}';

    protected $description = 'Revoke an integration API client';

    public function handle(): int
    {
        $client = ApiClient::find($this->argument('id'));

        if (! $client) {
            $this->error('API client not found.');
            return 1;
        }

        $client->active = false;
        $client->save();

        $this->info("Revoked API client {$client->id} ({$client->name}).");

        return 0;
    }
}
