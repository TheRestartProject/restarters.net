<?php

namespace App\Console\Commands;

use App\Network;
use App\Services\ApiClientService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ApiClientsCreate extends Command
{
    protected $signature = 'api-clients:create
                            {--name= : Display name for the integration client}
                            {--scopes=events:read : Comma-separated scopes}
                            {--origins= : Comma-separated allowed origins}
                            {--networks= : Comma-separated allowed network IDs}
                            {--rate=120 : Requests per minute}
                            {--expires-at= : Expiration datetime}';

    protected $description = 'Create a read-only integration API client and print its secret once';

    public function handle(ApiClientService $apiClientService): int
    {
        $name = trim((string) $this->option('name'));

        if ($name === '') {
            $this->error('The --name option is required.');
            return 1;
        }

        $rate = (int) $this->option('rate');
        if ($rate < 1) {
            $this->error('The --rate option must be greater than zero.');
            return 1;
        }

        $scopes = $this->parseCsvOption((string) $this->option('scopes'));
        $origins = $this->parseCsvOption((string) $this->option('origins'));
        $networkIds = array_values(array_filter(array_map('intval', $this->parseCsvOption((string) $this->option('networks'))), function ($id) {
            return $id > 0;
        }));

        if (! empty($networkIds) && Network::whereIn('id', $networkIds)->count() !== count($networkIds)) {
            $this->error('One or more network IDs do not exist.');
            return 1;
        }

        $expiresAt = null;
        if ($this->option('expires-at')) {
            $expiresAt = Carbon::parse((string) $this->option('expires-at'));
        }

        [$client, $plainToken] = $apiClientService->create([
            'name' => $name,
            'scopes' => $scopes ?: ['events:read'],
            'allowed_origins' => $origins ?: null,
            'allowed_network_ids' => $networkIds ?: null,
            'rate_limit_per_minute' => $rate,
            'active' => true,
            'expires_at' => $expiresAt,
        ]);

        $this->info('API client created.');
        $this->line("ID: {$client->id}");
        $this->line("Name: {$client->name}");
        $this->line("Token ID: {$client->token_hint}");
        $this->line("Token: {$plainToken}");
        $this->warn('Store this token now. It will not be shown again.');

        return 0;
    }

    private function parseCsvOption(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
