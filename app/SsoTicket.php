<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One-time, short-lived ticket letting the token-authenticated SPA establish
 * a Laravel web session via a top-level navigation to GET /auth/bridge.
 * Only the sha256 of the ticket is stored.
 */
class SsoTicket extends Model
{
    public const LIFETIME_SECONDS = 60;

    protected $fillable = ['user_id', 'ticket_hash', 'expires_at', 'used_at'];

    /**
     * Issue a ticket for the user; returns the plaintext to hand to the client.
     */
    public static function issue(User $user): string
    {
        $plaintext = Str::random(48);

        self::create([
            'user_id' => $user->id,
            'ticket_hash' => hash('sha256', $plaintext),
            'expires_at' => now()->addSeconds(self::LIFETIME_SECONDS),
        ]);

        return $plaintext;
    }

    /**
     * Consume a ticket: returns the user it was issued to, or null if the
     * ticket is unknown, expired, or already used. Single-use.
     */
    public static function consume(?string $plaintext): ?User
    {
        if (! $plaintext) {
            return null;
        }

        $ticket = self::where('ticket_hash', hash('sha256', $plaintext))->first();

        if (! $ticket || $ticket->used_at || $ticket->expires_at->isPast()) {
            return null;
        }

        $ticket->used_at = now();
        $ticket->save();

        return User::find($ticket->user_id);
    }

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
