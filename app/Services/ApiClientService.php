<?php

namespace App\Services;

use App\ApiClient;
use Illuminate\Support\Str;

class ApiClientService
{
    public function create(array $attributes)
    {
        $plainToken = Str::random(64);
        $client = ApiClient::create(array_merge($attributes, [
            'token_hash' => hash('sha256', $plainToken),
            'token_hint' => $this->makeTokenHint($plainToken),
        ]));

        return [$client, $plainToken];
    }

    public function rotate(ApiClient $client)
    {
        $plainToken = Str::random(64);

        $client->token_hash = hash('sha256', $plainToken);
        $client->token_hint = $this->makeTokenHint($plainToken);
        $client->active = true;
        $client->save();

        return $plainToken;
    }

    public function makeTokenHint($plainToken)
    {
        return substr($plainToken, 0, 6).'...'.substr($plainToken, -4);
    }
}
