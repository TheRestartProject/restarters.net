<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

/**
 * Issues a named Sanctum token for a user, for API access from outside the
 * site (a group's own tooling, a partner site). The token acts as that user,
 * so give it to an account with only the permissions the caller needs.
 */
class UserToken extends Command
{
    protected $signature = 'user:token {email : The account the token acts as} {name : Who or what the token is for, e.g. "Repair Together sync"}';

    protected $description = 'Issue a named API token for a user. The token is shown once.';

    // The Nuxt site names its login tokens "spa"; keep API keys distinguishable.
    private const RESERVED_NAMES = ['spa'];

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = trim($this->argument('name'));

        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error("User $email not found.");

            return self::FAILURE;
        }

        if ($name === '' || in_array(strtolower($name), self::RESERVED_NAMES, true)) {
            $this->error('Give the token a name that says who or what it is for (not "spa", which the site uses for logins).');

            return self::FAILURE;
        }

        $token = $user->createToken($name)->plainTextToken;

        $this->info("Token \"$name\" for $email (copy it now, it is not shown again):");
        $this->line($token);
        $this->line('Send it as the header  Authorization: Bearer <token>');
        $this->line('It stops working if the password on this account is changed or reset.');

        if (! $user->hasUserGivenConsent()) {
            $this->warn('This user has not given data consent, so the API will refuse their writes (403 consent_required). Reads work.');
        }

        return self::SUCCESS;
    }
}
